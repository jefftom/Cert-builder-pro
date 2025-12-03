/**
 * Properties Panel Component
 */

import { __ } from '@wordpress/i18n';
import {
	TextControl,
	SelectControl,
	RangeControl,
	ColorPicker,
	Button,
} from '@wordpress/components';

const PropertiesPanel = ({ elements = [], onUpdate, onDelete }) => {
	// Handle empty selection
	if (!elements || elements.length === 0) {
		return (
			<div className="cb-properties-panel cb-properties-empty">
				<p>{__('Select an element to edit its properties', 'certbuilder-pro')}</p>
			</div>
		);
	}

	// Handle multiple selection
	if (elements.length > 1) {
		return (
			<div className="cb-properties-panel">
				<div className="cb-properties-header">
					<h3>{__('Multiple Elements', 'certbuilder-pro')}</h3>
					<Button
						variant="link"
						onClick={onDelete}
						className="cb-delete-btn"
						isDestructive
					>
						{__('Delete All', 'certbuilder-pro')}
					</Button>
				</div>
				<div className="cb-properties-content">
					<div className="cb-multi-select-info">
						<p>{elements.length} {__('elements selected', 'certbuilder-pro')}</p>
						<p className="cb-property-description">
							{__('Changes will apply to all selected elements.', 'certbuilder-pro')}
						</p>
					</div>
					<div className="cb-property-group">
						<h4>{__('Common Properties', 'certbuilder-pro')}</h4>
						<RangeControl
							label={__('Rotation', 'certbuilder-pro')}
							value={0}
							onChange={(value) => onUpdate({ rotation: value })}
							min={-180}
							max={180}
						/>
					</div>
				</div>
			</div>
		);
	}

	// Single element selected
	const element = elements[0];

	const fonts = window.certbuilderBuilder?.fonts || [];
	const fontOptions = fonts.map((font) => ({
		label: font.name,
		value: font.id,
	}));

	const fields = window.certbuilderBuilder?.fields || {};
	const fieldOptions = [];
	Object.entries(fields).forEach(([groupKey, groupFields]) => {
		Object.entries(groupFields).forEach(([fieldKey, field]) => {
			fieldOptions.push({
				label: `${field.label} (${groupKey})`,
				value: fieldKey,
			});
		});
	});

	return (
		<div className="cb-properties-panel">
			<div className="cb-properties-header">
				<h3>{getElementTypeName(element.type)}</h3>
				<Button
					variant="link"
					onClick={onDelete}
					className="cb-delete-btn"
					isDestructive
				>
					{__('Delete', 'certbuilder-pro')}
				</Button>
			</div>

			<div className="cb-properties-content">
				{/* Position */}
				<div className="cb-property-group">
					<h4>{__('Position', 'certbuilder-pro')}</h4>
					<div className="cb-property-row">
						<TextControl
							label="X"
							type="number"
							value={element.x || 0}
							onChange={(value) => onUpdate({ x: parseInt(value, 10) })}
						/>
						<TextControl
							label="Y"
							type="number"
							value={element.y || 0}
							onChange={(value) => onUpdate({ y: parseInt(value, 10) })}
						/>
					</div>
					<RangeControl
						label={__('Rotation', 'certbuilder-pro')}
						value={element.rotation || 0}
						onChange={(value) => onUpdate({ rotation: value })}
						min={-180}
						max={180}
					/>
				</div>

				{/* Text Properties */}
				{(element.type === 'text' || element.type === 'dynamic_field') && (
					<>
						{element.type === 'text' && (
							<div className="cb-property-group">
								<h4>{__('Content', 'certbuilder-pro')}</h4>
								<TextControl
									label={__('Text', 'certbuilder-pro')}
									value={element.content || ''}
									onChange={(value) => onUpdate({ content: value })}
								/>
							</div>
						)}

						{element.type === 'dynamic_field' && (
							<div className="cb-property-group">
								<h4>{__('Field', 'certbuilder-pro')}</h4>
								<SelectControl
									label={__('Data Field', 'certbuilder-pro')}
									value={element.field || ''}
									options={fieldOptions}
									onChange={(value) => onUpdate({ field: value })}
								/>
								<TextControl
									label={__('Prefix', 'certbuilder-pro')}
									value={element.prefix || ''}
									onChange={(value) => onUpdate({ prefix: value })}
								/>
								<TextControl
									label={__('Suffix', 'certbuilder-pro')}
									value={element.suffix || ''}
									onChange={(value) => onUpdate({ suffix: value })}
								/>
							</div>
						)}

						<div className="cb-property-group">
							<h4>{__('Typography', 'certbuilder-pro')}</h4>
							<SelectControl
								label={__('Font', 'certbuilder-pro')}
								value={element.fontFamily || 'open-sans'}
								options={fontOptions}
								onChange={(value) => onUpdate({ fontFamily: value })}
							/>
							<RangeControl
								label={__('Size', 'certbuilder-pro')}
								value={element.fontSize || 24}
								onChange={(value) => onUpdate({ fontSize: value })}
								min={8}
								max={120}
							/>
							<SelectControl
								label={__('Weight', 'certbuilder-pro')}
								value={element.fontWeight || 'normal'}
								options={[
									{ label: __('Normal', 'certbuilder-pro'), value: 'normal' },
									{ label: __('Bold', 'certbuilder-pro'), value: 'bold' },
								]}
								onChange={(value) => onUpdate({ fontWeight: value })}
							/>
							<SelectControl
								label={__('Alignment', 'certbuilder-pro')}
								value={element.align || 'left'}
								options={[
									{ label: __('Left', 'certbuilder-pro'), value: 'left' },
									{ label: __('Center', 'certbuilder-pro'), value: 'center' },
									{ label: __('Right', 'certbuilder-pro'), value: 'right' },
								]}
								onChange={(value) => onUpdate({ align: value })}
							/>
						</div>

						<div className="cb-property-group">
							<h4>{__('Color', 'certbuilder-pro')}</h4>
							<ColorPicker
								color={element.fill || '#000000'}
								onChange={(color) => onUpdate({ fill: color })}
								enableAlpha={false}
							/>
						</div>
					</>
				)}

				{/* Image Properties */}
				{element.type === 'image' && (
					<div className="cb-property-group">
						<h4>{__('Image', 'certbuilder-pro')}</h4>
						<TextControl
							label={__('Image URL', 'certbuilder-pro')}
							value={element.src || ''}
							onChange={(value) => onUpdate({ src: value })}
						/>
						<Button
							variant="secondary"
							onClick={() => {
								const frame = wp.media({
									title: __('Select Image', 'certbuilder-pro'),
									multiple: false,
									library: { type: 'image' },
								});

								frame.on('select', () => {
									const attachment = frame.state().get('selection').first().toJSON();
									onUpdate({
										src: attachment.url,
										width: attachment.width,
										height: attachment.height,
									});
								});

								frame.open();
							}}
						>
							{__('Select from Media Library', 'certbuilder-pro')}
						</Button>
						<div className="cb-property-row">
							<TextControl
								label={__('Width', 'certbuilder-pro')}
								type="number"
								value={element.width || 150}
								onChange={(value) => onUpdate({ width: parseInt(value, 10) })}
							/>
							<TextControl
								label={__('Height', 'certbuilder-pro')}
								type="number"
								value={element.height || 150}
								onChange={(value) => onUpdate({ height: parseInt(value, 10) })}
							/>
						</div>
					</div>
				)}

				{/* Shape Properties */}
				{element.type === 'shape' && (
					<div className="cb-property-group">
						<h4>{__('Shape', 'certbuilder-pro')}</h4>
						<SelectControl
							label={__('Shape Type', 'certbuilder-pro')}
							value={element.shapeType || 'rectangle'}
							options={[
								{ label: __('Rectangle', 'certbuilder-pro'), value: 'rectangle' },
								{ label: __('Circle', 'certbuilder-pro'), value: 'circle' },
							]}
							onChange={(value) => onUpdate({ shapeType: value })}
						/>
						<div className="cb-property-row">
							<TextControl
								label={__('Width', 'certbuilder-pro')}
								type="number"
								value={element.width || 100}
								onChange={(value) => onUpdate({ width: parseInt(value, 10) })}
							/>
							<TextControl
								label={__('Height', 'certbuilder-pro')}
								type="number"
								value={element.height || 100}
								onChange={(value) => onUpdate({ height: parseInt(value, 10) })}
							/>
						</div>
						<h4>{__('Fill Color', 'certbuilder-pro')}</h4>
						<ColorPicker
							color={element.fill || '#3b82f6'}
							onChange={(color) => onUpdate({ fill: color })}
							enableAlpha={false}
						/>
						<TextControl
							label={__('Border Color', 'certbuilder-pro')}
							value={element.stroke || ''}
							onChange={(value) => onUpdate({ stroke: value })}
						/>
						<RangeControl
							label={__('Border Width', 'certbuilder-pro')}
							value={element.strokeWidth || 0}
							onChange={(value) => onUpdate({ strokeWidth: value })}
							min={0}
							max={20}
						/>
					</div>
				)}

				{/* Line Properties */}
				{element.type === 'line' && (
					<div className="cb-property-group">
						<h4>{__('Line', 'certbuilder-pro')}</h4>
						<TextControl
							label={__('Width', 'certbuilder-pro')}
							type="number"
							value={element.width || 200}
							onChange={(value) => onUpdate({ width: parseInt(value, 10) })}
						/>
						<ColorPicker
							color={element.stroke || '#000000'}
							onChange={(color) => onUpdate({ stroke: color })}
							enableAlpha={false}
						/>
						<RangeControl
							label={__('Thickness', 'certbuilder-pro')}
							value={element.strokeWidth || 2}
							onChange={(value) => onUpdate({ strokeWidth: value })}
							min={1}
							max={20}
						/>
					</div>
				)}

				{/* QR Code Properties */}
				{element.type === 'qr_code' && (
					<div className="cb-property-group">
						<h4>{__('QR Code', 'certbuilder-pro')}</h4>
						<p className="cb-property-description">
							{__('QR code will automatically link to the certificate verification page.', 'certbuilder-pro')}
						</p>
						<RangeControl
							label={__('Size', 'certbuilder-pro')}
							value={element.size || 80}
							onChange={(value) => onUpdate({ size: value, width: value, height: value })}
							min={40}
							max={200}
						/>
					</div>
				)}
			</div>
		</div>
	);
};

function getElementTypeName(type) {
	const names = {
		text: __('Text', 'certbuilder-pro'),
		dynamic_field: __('Dynamic Field', 'certbuilder-pro'),
		image: __('Image', 'certbuilder-pro'),
		shape: __('Shape', 'certbuilder-pro'),
		line: __('Line', 'certbuilder-pro'),
		qr_code: __('QR Code', 'certbuilder-pro'),
	};
	return names[type] || type;
}

export default PropertiesPanel;
