from odoo import fields, models


class CvPositionAttribute(models.Model):
    _name = 'cv.position.attribute'
    _description = 'Imported position attribute'

    position_id = fields.Many2one('cv.position', required=True, ondelete='cascade')
    title = fields.Char()
    type = fields.Char()
    result = fields.Text()
