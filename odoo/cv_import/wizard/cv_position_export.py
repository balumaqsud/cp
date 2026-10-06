import requests

from odoo import _, fields, models
from odoo.exceptions import UserError


class CvPositionExportWizard(models.TransientModel):
    _name = 'cv.position.export.wizard'
    _description = 'Export position'

    title = fields.Char(required=True)
    base_url = fields.Char(
        required=True,
        default=lambda self: self.env['ir.config_parameter'].sudo().get_param(
            'cv_import.base_url',
            'http://host.docker.internal:8000',
        ),
    )
    line_ids = fields.One2many('cv.position.export.line', 'wizard_id')

    def action_export(self):
        self.ensure_one()
        token = self.env['ir.config_parameter'].sudo().get_param('cv_import.export_token')
        if not token:
            raise UserError(_('Set the export token before exporting.'))

        try:
            response = requests.post(
                '%s/api/positions' % (self.base_url or '').rstrip('/'),
                json={
                    'title': self.title,
                    'attributes': [
                        {'title': line.title, 'type': line.type or ''}
                        for line in self.line_ids
                    ],
                },
                headers={'Authorization': 'Bearer %s' % token},
                timeout=10,
            )
        except requests.RequestException as error:
            raise UserError(_('Could not reach the course project.')) from error

        if response.status_code != 201:
            raise UserError(_('The course project rejected the export.'))

        position = self.env['cv.position'].create({
            'title': self.title,
        })
        for line in self.line_ids:
            self.env['cv.position.attribute'].create({
                'position_id': position.id,
                'title': line.title,
                'type': line.type or '',
            })

        return {
            'type': 'ir.actions.act_window',
            'name': position.title,
            'res_model': 'cv.position',
            'res_id': position.id,
            'view_mode': 'form',
            'target': 'current',
        }


class CvPositionExportLine(models.TransientModel):
    _name = 'cv.position.export.line'
    _description = 'Export attribute line'

    wizard_id = fields.Many2one('cv.position.export.wizard', required=True, ondelete='cascade')
    title = fields.Char(required=True)
    type = fields.Char()
