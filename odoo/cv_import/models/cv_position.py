from odoo import fields, models


class CvPosition(models.Model):
    _name = 'cv.position'
    _description = 'Imported position'
    _rec_name = 'title'

    title = fields.Char(required=True)
    published_cv_count = fields.Integer()
    api_token = fields.Char(index=True)
    imported_at = fields.Datetime()
    attribute_ids = fields.One2many('cv.position.attribute', 'position_id')

    _sql_constraints = [
        ('api_token_unique', 'unique(api_token)', 'API token must be unique.'),
    ]
