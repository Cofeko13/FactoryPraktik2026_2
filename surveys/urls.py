from django.urls import path
from . import views

app_name = 'surveys'

urlpatterns = [
    # Конструктор опросов
    path('', views.survey_list, name='survey_list'),
    path('create/', views.create_survey, name='create_survey'),
    path('<int:survey_id>/', views.survey_detail, name='survey_detail'),
    path('<int:survey_id>/edit/', views.edit_survey, name='edit_survey'),
    path('<int:survey_id>/delete/', views.delete_survey, name='delete_survey'),
    path('<int:survey_id>/preview/', views.preview_survey, name='preview_survey'),
    path('<int:survey_id>/reorder/', views.reorder_questions, name='reorder_questions'),
    
    # Сбор ответов
    path('<int:survey_id>/take/', views.take_survey, name='take_survey'),
    path('thanks/', views.survey_thanks, name='thanks'),
    
    # Дашборд
    path('<int:survey_id>/dashboard/', views.dashboard, name='dashboard'),
    
    # Экспорт
    path('<int:survey_id>/export/csv/', views.export_csv, name='export_csv'),
    path('<int:survey_id>/export/excel/', views.export_excel, name='export_excel'),
    path('<int:survey_id>/export/pdf/', views.export_pdf, name='export_pdf'),
    
    # Администрирование
    path('admin/', views.admin_dashboard, name='admin_dashboard'),
    path('<int:survey_id>/permissions/', views.manage_permissions, name='manage_permissions'),
]