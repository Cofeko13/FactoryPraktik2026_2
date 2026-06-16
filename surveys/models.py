from django.db import models
from django.contrib.auth.models import User
from django.utils import timezone

class Survey(models.Model):
    title = models.CharField(max_length=200)
    description = models.TextField(blank=True)
    unique_id = models.CharField(max_length=50, unique=True)
    created_by = models.ForeignKey(User, on_delete=models.CASCADE, related_name='surveys')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    is_published = models.BooleanField(default=False)
    published_at = models.DateTimeField(null=True, blank=True)

    def __str__(self):
        return self.title

class Question(models.Model):
    QUESTION_TYPES = [
        ('radio', 'Одиночный выбор'),
        ('checkbox', 'Множественный выбор'),
        ('text', 'Текстовый ответ'),
        ('scale', 'Шкала оценок 1-10'),
        ('matrix', 'Матрица (таблица с оценками)'),
    ]
    
    survey = models.ForeignKey(Survey, on_delete=models.CASCADE, related_name='questions')
    question_text = models.TextField()
    question_type = models.CharField(max_length=20, choices=QUESTION_TYPES)
    order = models.IntegerField(default=0)
    options = models.JSONField(default=list, blank=True)  # Для radio, checkbox, matrix
    is_required = models.BooleanField(default=True)

    class Meta:
        ordering = ['order']

    def __str__(self):
        return f"{self.question_text[:50]}..."

class Response(models.Model):
    survey = models.ForeignKey(Survey, on_delete=models.CASCADE, related_name='responses')
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='responses')
    started_at = models.DateTimeField(default=timezone.now)
    completed_at = models.DateTimeField(null=True, blank=True)
    is_completed = models.BooleanField(default=False)
    progress_data = models.JSONField(default=dict, blank=True)  # Сохранение прогресса

    def __str__(self):
        return f"Response #{self.id} - {self.user.username}"

class Answer(models.Model):
    response = models.ForeignKey(Response, on_delete=models.CASCADE, related_name='answers')
    question = models.ForeignKey(Question, on_delete=models.CASCADE)
    answer_text = models.TextField(blank=True, null=True)
    answer_options = models.JSONField(default=list, blank=True)  # Для множественного выбора

    def __str__(self):
        return f"Answer to {self.question.question_text[:30]}..."

class SurveyPermission(models.Model):
    ROLE_CHOICES = [
        ('owner', 'Владелец'),
        ('editor', 'Редактор'),
        ('analyst', 'Аналитик'),
    ]
    
    survey = models.ForeignKey(Survey, on_delete=models.CASCADE, related_name='permissions')
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    role = models.CharField(max_length=20, choices=ROLE_CHOICES)

    class Meta:
        unique_together = ['survey', 'user']