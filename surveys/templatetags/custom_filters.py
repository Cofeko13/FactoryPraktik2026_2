from django import template

register = template.Library()

@register.filter
def get_item(dictionary, key):
    return dictionary.get(str(key))

@register.filter
def get_keys(dict_obj):
    if dict_obj:
        return dict_obj.keys()
    return []