/**
 * Fields Panel Component
 */

import { __ } from '@wordpress/i18n';

const FieldsPanel = ({ onAddField }) => {
	const fields = window.certbuilderBuilder?.fields || {};

	const groupLabels = {
		certificate: __('Certificate', 'certbuilder-pro'),
		user: __('Student', 'certbuilder-pro'),
		site: __('Site', 'certbuilder-pro'),
		course: __('Course', 'certbuilder-pro'),
		quiz: __('Quiz', 'certbuilder-pro'),
		completion: __('Completion', 'certbuilder-pro'),
		instructor: __('Instructor', 'certbuilder-pro'),
		group: __('Group', 'certbuilder-pro'),
		cumulative: __('Cumulative', 'certbuilder-pro'),
		achievement: __('Achievement', 'certbuilder-pro'),
		issuer: __('Issuer', 'certbuilder-pro'),
		custom: __('Custom', 'certbuilder-pro'),
	};

	return (
		<div className="cb-fields-panel">
			<h3>{__('Dynamic Fields', 'certbuilder-pro')}</h3>
			<p className="cb-panel-description">
				{__('Click a field to add it to your certificate. Values are filled automatically.', 'certbuilder-pro')}
			</p>

			{Object.entries(fields).map(([groupKey, groupFields]) => (
				<div key={groupKey} className="cb-field-group">
					<h4 className="cb-field-group-title">
						{groupLabels[groupKey] || groupKey}
					</h4>
					<div className="cb-fields-list">
						{Object.entries(groupFields).map(([fieldKey, field]) => (
							<button
								key={fieldKey}
								className="cb-field-btn"
								onClick={() => onAddField(fieldKey)}
								title={field.description}
							>
								<span className="cb-field-label">{field.label}</span>
								<code className="cb-field-key">{`{{${fieldKey}}}`}</code>
							</button>
						))}
					</div>
				</div>
			))}

			{Object.keys(fields).length === 0 && (
				<p className="cb-empty-message">
					{__('No fields available.', 'certbuilder-pro')}
				</p>
			)}
		</div>
	);
};

export default FieldsPanel;
