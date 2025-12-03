/**
 * Elements Panel Component
 */

import { __ } from '@wordpress/i18n';

const ElementsPanel = ({ onAddElement }) => {
	const elements = [
		{
			type: 'text',
			label: __('Text', 'certbuilder-pro'),
			icon: 'T',
			description: __('Add static text', 'certbuilder-pro'),
		},
		{
			type: 'dynamic_field',
			label: __('Dynamic Field', 'certbuilder-pro'),
			icon: '⚡',
			description: __('Add data from LMS', 'certbuilder-pro'),
			options: { field: 'student_name' },
		},
		{
			type: 'image',
			label: __('Image', 'certbuilder-pro'),
			icon: '🖼',
			description: __('Add logo or image', 'certbuilder-pro'),
		},
		{
			type: 'shape',
			label: __('Rectangle', 'certbuilder-pro'),
			icon: '▢',
			description: __('Add rectangle shape', 'certbuilder-pro'),
			options: { shapeType: 'rectangle' },
		},
		{
			type: 'shape',
			label: __('Circle', 'certbuilder-pro'),
			icon: '○',
			description: __('Add circle shape', 'certbuilder-pro'),
			options: { shapeType: 'circle' },
		},
		{
			type: 'line',
			label: __('Line', 'certbuilder-pro'),
			icon: '─',
			description: __('Add horizontal line', 'certbuilder-pro'),
		},
		{
			type: 'qr_code',
			label: __('QR Code', 'certbuilder-pro'),
			icon: '▣',
			description: __('Verification QR code', 'certbuilder-pro'),
		},
	];

	return (
		<div className="cb-elements-panel">
			<h3>{__('Add Elements', 'certbuilder-pro')}</h3>
			<p className="cb-panel-description">
				{__('Click or drag elements to add them to your certificate.', 'certbuilder-pro')}
			</p>

			<div className="cb-elements-grid">
				{elements.map((element, index) => (
					<button
						key={`${element.type}-${index}`}
						className="cb-element-btn"
						onClick={() => onAddElement(element.type, element.options)}
						title={element.description}
					>
						<span className="cb-element-icon">{element.icon}</span>
						<span className="cb-element-label">{element.label}</span>
					</button>
				))}
			</div>
		</div>
	);
};

export default ElementsPanel;
