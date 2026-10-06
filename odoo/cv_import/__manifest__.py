{
    'name': 'CV Import',
    'version': '18.0.1.0.0',
    'category': 'Human Resources',
    'summary': 'Store imported positions and aggregated CV results',
    'depends': ['base'],
    'data': [
        'security/ir.model.access.csv',
        'views/cv_position_views.xml',
    ],
    'installable': True,
    'application': True,
    'license': 'LGPL-3',
}
