import json

import requests

from odoo import _, fields, models
from odoo.exceptions import UserError


class CvPositionImportWizard(models.TransientModel):
    _name = 'cv.position.import.wizard'
    _description = 'Import position'

    api_token = fields.Char(required=True)
    base_url = fields.Char(
        required=True,
        default=lambda self: self.env['ir.config_parameter'].sudo().get_param(
            'cv_import.base_url',
            'http://host.docker.internal:8000',
        ),
    )

    def action_import(self):
        self.ensure_one()
        payload = self._fetch_payload()
        position = self._store(payload)

        return {
            'type': 'ir.actions.act_window',
            'name': position.title,
            'res_model': 'cv.position',
            'res_id': position.id,
            'view_mode': 'form',
            'target': 'current',
        }

    def _fetch_payload(self):
        url = '%s/api/positions' % (self.base_url or '').rstrip('/')
        try:
            response = requests.get(
                url,
                headers={'Authorization': 'Bearer %s' % self.api_token},
                timeout=10,
            )
        except requests.RequestException as error:
            raise UserError(_('Could not reach the course project.')) from error

        if response.status_code == 401:
            raise UserError(_('The API token was rejected.'))
        if response.status_code != 200:
            raise UserError(_('The course project returned an error.'))
        try:
            payload = response.json()
        except ValueError as error:
            raise UserError(_('The course project returned invalid JSON.')) from error
        if not isinstance(payload, dict) or not payload.get('title') or not isinstance(payload.get('attributes'), list):
            raise UserError(_('The course project returned invalid JSON.'))

        return payload

    def _store(self, payload):
        positions = self.env['cv.position']
        position = positions.search([('api_token', '=', self.api_token)], limit=1)
        values = {
            'title': payload['title'],
            'published_cv_count': payload.get('publishedCvCount') or 0,
            'imported_at': fields.Datetime.now(),
            'api_token': self.api_token,
        }
        if position:
            position.attribute_ids.unlink()
            position.write(values)
        else:
            position = positions.create(values)

        for row in payload['attributes']:
            result = row.get('result')
            self.env['cv.position.attribute'].create({
                'position_id': position.id,
                'title': row.get('title') or '',
                'type': row.get('type') or '',
                'result': json.dumps(result if result is not None else {}, ensure_ascii=False),
            })

        return position
