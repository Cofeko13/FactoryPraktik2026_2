from django.shortcuts import render, get_object_or_404, redirect
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.http import JsonResponse, HttpResponse
from django.db.models import Count, Avg, Q
from django.core.paginator import Paginator
from django.utils import timezone
from .models import Survey, Question, Response, Answer, SurveyPermission
from django.contrib.auth.forms import UserCreationForm
from django.contrib.auth import login
import json
import csv
from io import BytesIO
from datetime import datetime

# 2.1 Конструктор опросов
@login_required
def survey_list(request):
    surveys = Survey.objects.filter(
        Q(created_by=request.user) | Q(permissions__user=request.user)
    ).distinct()
    paginator = Paginator(surveys, 10)
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)
    return render(request, 'surveys/survey_list.html', {'page_obj': page_obj})

@login_required
def create_survey(request):
    if request.method == 'POST':
        title = request.POST.get('title')
        description = request.POST.get('description')
        unique_id = f"SURV-{timezone.now().strftime('%Y%m%d%H%M%S')}-{User.objects.count()+1}"
        
        survey = Survey.objects.create(
            title=title,
            description=description,
            unique_id=unique_id,
            created_by=request.user
        )
        
        # Создаем вопросы
        question_texts = request.POST.getlist('question_text[]')
        question_types = request.POST.getlist('question_type[]')
        
        for i, (text, qtype) in enumerate(zip(question_texts, question_types)):
            options = []
            if qtype in ['radio', 'checkbox']:
                option_keys = request.POST.getlist(f'options_{i}[]')
                options = option_keys if option_keys else ['Вариант 1', 'Вариант 2']
            elif qtype == 'matrix':
                rows = request.POST.getlist(f'matrix_rows_{i}[]')
                cols = request.POST.getlist(f'matrix_cols_{i}[]')
                options = {'rows': rows, 'cols': cols}
            elif qtype == 'scale':
                options = list(range(1, 11))
            
            Question.objects.create(
                survey=survey,
                question_text=text,
                question_type=qtype,
                order=i,
                options=options
            )
        
        return redirect('surveys:survey_detail', survey_id=survey.id)
    
    return render(request, 'surveys/create_survey.html')

@login_required
def survey_detail(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    questions = survey.questions.all()
    return render(request, 'surveys/survey_detail.html', {
        'survey': survey,
        'questions': questions
    })

@login_required
def preview_survey(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    questions = survey.questions.all()
    return render(request, 'surveys/preview_survey.html', {
        'survey': survey,
        'questions': questions
    })

@login_required
def edit_survey(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    if request.method == 'POST':
        survey.title = request.POST.get('title')
        survey.description = request.POST.get('description')
        survey.save()
        return redirect('surveys:survey_detail', survey_id=survey.id)
    
    questions = survey.questions.all()
    return render(request, 'surveys/edit_survey.html', {
        'survey': survey,
        'questions': questions
    })

@login_required
def delete_survey(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    if request.method == 'POST':
        survey.delete()
        return redirect('surveys:survey_list')
    return render(request, 'surveys/delete_confirm.html', {'survey': survey})

@login_required
def reorder_questions(request, survey_id):
    if request.method == 'POST':
        survey = get_object_or_404(Survey, id=survey_id)
        order_data = json.loads(request.POST.get('order_data'))
        for item in order_data:
            question = Question.objects.get(id=item['id'], survey=survey)
            question.order = item['order']
            question.save()
        return JsonResponse({'status': 'success'})
    return JsonResponse({'status': 'error'}, status=400)

# 2.2 Сбор ответов
@login_required
def take_survey(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    questions = survey.questions.all()
    
    # Проверяем, есть ли незавершенный ответ
    existing_response = Response.objects.filter(
        survey=survey,
        user=request.user,
        is_completed=False
    ).first()
    
    if request.method == 'POST':
        if not existing_response:
            response = Response.objects.create(
                survey=survey,
                user=request.user,
                started_at=timezone.now()
            )
        else:
            response = existing_response
        
        # Сохраняем прогресс
        progress = {}
        for question in questions:
            answer_key = f'question_{question.id}'
            if question.question_type == 'checkbox':
                values = request.POST.getlist(answer_key)
                progress[str(question.id)] = values
                answer_text = ','.join(values)
                answer_options = values
            elif question.question_type == 'text':
                value = request.POST.get(answer_key, '')
                progress[str(question.id)] = value
                answer_text = value
                answer_options = []
            else:
                value = request.POST.get(answer_key, '')
                progress[str(question.id)] = value
                answer_text = value
                answer_options = []
            
            # Сохраняем ответ
            Answer.objects.update_or_create(
                response=response,
                question=question,
                defaults={
                    'answer_text': answer_text if answer_text else None,
                    'answer_options': answer_options if question.question_type == 'checkbox' else []
                }
            )
        
        response.progress_data = progress
        
        if request.POST.get('complete'):
            response.is_completed = True
            response.completed_at = timezone.now()
            response.save()
            return redirect('surveys:thanks')
        
        response.save()
        return redirect('surveys:take_survey', survey_id=survey_id)
    
    return render(request, 'surveys/take_survey.html', {
        'survey': survey,
        'questions': questions,
        'response': existing_response
    })

def survey_thanks(request):
    return render(request, 'surveys/thanks.html')

# 2.3 Дашборд с диаграммами
@login_required
def dashboard(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    questions = survey.questions.all()
    
    stats = {}
    total_responses = Response.objects.filter(survey=survey, is_completed=True).count()
    
    for question in questions:
        answers = Answer.objects.filter(
            response__survey=survey,
            response__is_completed=True,
            question=question
        )
        
        if question.question_type in ['radio', 'checkbox']:
            # Подсчет вариантов
            option_counts = {}
            for answer in answers:
                if question.question_type == 'radio':
                    option = answer.answer_text or 'Без ответа'
                else:
                    option = answer.answer_text or 'Без ответа'
                
                if option not in option_counts:
                    option_counts[option] = 0
                option_counts[option] += 1
            
            stats[str(question.id)] = {
                'type': question.question_type,
                'data': option_counts,
                'total': len(answers)
            }
            
        elif question.question_type == 'scale':
            # Среднее значение
            values = []
            for a in answers:
                try:
                    if a.answer_text:
                        val = float(a.answer_text)
                        values.append(val)
                except:
                    pass
            
            avg = sum(values) / len(values) if values else 0
            stats[str(question.id)] = {
                'type': 'scale',
                'average': round(avg, 2),
                'count': len(values),
                'values': values
            }
            
        elif question.question_type == 'text':
            stats[str(question.id)] = {
                'type': 'text',
                'count': len(answers),
                'answers': [a.answer_text for a in answers if a.answer_text][:10]
            }
            
        elif question.question_type == 'matrix':
            # Упрощенная версия для матрицы
            matrix_data = {}
            for answer in answers:
                if answer.answer_text:
                    try:
                        data = json.loads(answer.answer_text)
                        for row, col_values in data.items():
                            if row not in matrix_data:
                                matrix_data[row] = {}
                            for col, val in col_values.items():
                                if col not in matrix_data[row]:
                                    matrix_data[row][col] = []
                                matrix_data[row][col].append(val)
                    except:
                        pass
            
            stats[str(question.id)] = {
                'type': 'matrix',
                'data': matrix_data
            }
    
    return render(request, 'surveys/dashboard.html', {
        'survey': survey,
        'stats': stats,
        'total_responses': total_responses
    })

# 2.4 Экспорт данных
@login_required
def export_csv(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    responses = Response.objects.filter(survey=survey, is_completed=True)
    
    response = HttpResponse(content_type='text/csv; charset=utf-8')
    response['Content-Disposition'] = f'attachment; filename="{survey.title}_results.csv"'
    
    writer = csv.writer(response)
    headers = ['Пользователь', 'Начало', 'Завершение']
    questions = survey.questions.all()
    headers.extend([f'Q{i+1}' for i, q in enumerate(questions)])
    writer.writerow(headers)
    
    for resp in responses:
        row = [
            resp.user.username,
            resp.started_at.strftime('%d.%m.%Y %H:%M') if resp.started_at else '',
            resp.completed_at.strftime('%d.%m.%Y %H:%M') if resp.completed_at else ''
        ]
        for question in questions:
            answer = Answer.objects.filter(response=resp, question=question).first()
            if answer:
                if question.question_type == 'checkbox':
                    row.append(', '.join(answer.answer_options) if answer.answer_options else '')
                else:
                    row.append(answer.answer_text or '')
            else:
                row.append('')
        writer.writerow(row)
    
    return response

@login_required
def export_excel(request, survey_id):
    try:
        import xlsxwriter
    except ImportError:
        return HttpResponse("Для экспорта в Excel установите пакет xlsxwriter: pip install xlsxwriter", status=500)
    
    survey = get_object_or_404(Survey, id=survey_id)
    responses = Response.objects.filter(survey=survey, is_completed=True)
    
    output = BytesIO()
    workbook = xlsxwriter.Workbook(output)
    worksheet = workbook.add_worksheet('Результаты')
    
    row = 0
    col = 0
    headers = ['Пользователь', 'Начало', 'Завершение']
    questions = survey.questions.all()
    headers.extend([f'Q{i+1}' for i, q in enumerate(questions)])
    
    for header in headers:
        worksheet.write(row, col, header)
        col += 1
    
    row += 1
    for resp in responses:
        col = 0
        worksheet.write(row, col, resp.user.username)
        col += 1
        worksheet.write(row, col, resp.started_at.strftime('%d.%m.%Y %H:%M') if resp.started_at else '')
        col += 1
        worksheet.write(row, col, resp.completed_at.strftime('%d.%m.%Y %H:%M') if resp.completed_at else '')
        col += 1
        
        for question in questions:
            answer = Answer.objects.filter(response=resp, question=question).first()
            if answer:
                if question.question_type == 'checkbox':
                    worksheet.write(row, col, ', '.join(answer.answer_options) if answer.answer_options else '')
                else:
                    worksheet.write(row, col, answer.answer_text or '')
            col += 1
        row += 1
    
    workbook.close()
    output.seek(0)
    
    response = HttpResponse(
        output,
        content_type='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    )
    response['Content-Disposition'] = f'attachment; filename="{survey.title}_results.xlsx"'
    return response

@login_required
def export_pdf(request, survey_id):
    try:
        from reportlab.pdfgen import canvas
        from reportlab.lib.pagesizes import A4
        from reportlab.lib.units import inch
    except ImportError:
        return HttpResponse("Для экспорта в PDF установите пакет reportlab: pip install reportlab", status=500)
    
    survey = get_object_or_404(Survey, id=survey_id)
    
    response = HttpResponse(content_type='application/pdf')
    response['Content-Disposition'] = f'attachment; filename="{survey.title}_report.pdf"'
    
    buffer = BytesIO()
    c = canvas.Canvas(buffer, pagesize=A4)
    width, height = A4
    
    y = height - 50
    c.drawString(50, y, f"Отчет по опросу: {survey.title}")
    y -= 20
    c.drawString(50, y, f"ID: {survey.unique_id}")
    y -= 20
    c.drawString(50, y, f"Создан: {survey.created_at.strftime('%d.%m.%Y %H:%M')}")
    y -= 20
    
    responses = Response.objects.filter(survey=survey, is_completed=True)
    c.drawString(50, y, f"Всего ответов: {responses.count()}")
    y -= 30
    
    questions = survey.questions.all()
    for i, question in enumerate(questions):
        if y < 100:
            c.showPage()
            y = height - 50
        
        c.drawString(50, y, f"Вопрос {i+1}: {question.question_text}")
        y -= 15
        
        answers = Answer.objects.filter(
            response__survey=survey,
            response__is_completed=True,
            question=question
        )
        
        if question.question_type == 'radio':
            option_counts = {}
            for answer in answers:
                option = answer.answer_text or 'Без ответа'
                if option not in option_counts:
                    option_counts[option] = 0
                option_counts[option] += 1
            
            for option, count in option_counts.items():
                if y < 50:
                    c.showPage()
                    y = height - 50
                c.drawString(70, y, f"• {option[:40]}: {count} ({count*100//len(answers) if answers else 0}%)")
                y -= 15
                
        elif question.question_type == 'checkbox':
            option_counts = {}
            for answer in answers:
                if answer.answer_options:
                    for opt in answer.answer_options:
                        if opt not in option_counts:
                            option_counts[opt] = 0
                        option_counts[opt] += 1
                else:
                    option = 'Без ответа'
                    if option not in option_counts:
                        option_counts[option] = 0
                    option_counts[option] += 1
            
            for option, count in option_counts.items():
                if y < 50:
                    c.showPage()
                    y = height - 50
                c.drawString(70, y, f"• {option[:40]}: {count}")
                y -= 15
                
        elif question.question_type == 'scale':
            values = []
            for a in answers:
                try:
                    if a.answer_text:
                        values.append(float(a.answer_text))
                except:
                    pass
            
            if values:
                avg = sum(values) / len(values)
                c.drawString(70, y, f"Среднее: {avg:.2f}")
                y -= 15
                c.drawString(70, y, f"Количество: {len(values)}")
                y -= 15
                c.drawString(70, y, f"Мин: {min(values)}, Макс: {max(values)}")
                y -= 15
        
        elif question.question_type == 'text':
            c.drawString(70, y, f"Всего ответов: {len(answers)}")
            y -= 15
            for answer in answers[:5]:
                if y < 50:
                    c.showPage()
                    y = height - 50
                text = answer.answer_text or ''
                if len(text) > 50:
                    text = text[:47] + '...'
                c.drawString(70, y, f"• {text}")
                y -= 15
        
        y -= 15
    
    c.save()
    pdf = buffer.getvalue()
    buffer.close()
    response.write(pdf)
    return response

# 2.5 Администрирование
@login_required
def admin_dashboard(request):
    if not request.user.is_superuser:
        return redirect('surveys:survey_list')
    
    total_surveys = Survey.objects.count()
    total_responses = Response.objects.filter(is_completed=True).count()
    total_users = User.objects.count()
    
    surveys = Survey.objects.all().order_by('-created_at')[:10]
    
    return render(request, 'surveys/admin_dashboard.html', {
        'total_surveys': total_surveys,
        'total_responses': total_responses,
        'total_users': total_users,
        'surveys': surveys
    })

@login_required
def manage_permissions(request, survey_id):
    survey = get_object_or_404(Survey, id=survey_id)
    
    if request.method == 'POST':
        user_id = request.POST.get('user_id')
        role = request.POST.get('role')
        if user_id and role:
            try:
                user = get_object_or_404(User, id=user_id)
                SurveyPermission.objects.create(
                    survey=survey,
                    user=user,
                    role=role
                )
            except:
                pass
        return redirect('surveys:manage_permissions', survey_id=survey_id)
    
    permissions = SurveyPermission.objects.filter(survey=survey)
    users = User.objects.exclude(id=survey.created_by.id)
    
    return render(request, 'surveys/permissions.html', {
        'survey': survey,
        'permissions': permissions,
        'users': users
    })
def register(request):
    if request.method == 'POST':
        form = UserCreationForm(request.POST)
        if form.is_valid():
            user = form.save()
            login(request, user)
            return redirect('surveys:survey_list')
    else:
        form = UserCreationForm()
    return render(request, 'registration/register.html', {'form': form})