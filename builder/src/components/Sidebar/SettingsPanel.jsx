/**
 * Settings Panel Component
 */

import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	ColorPicker,
	RangeControl,
	TextControl,
} from '@wordpress/components';

const SettingsPanel = ({ template, onUpdate }) => {
	if (!template?.data) return null;

	const { data } = template;

	const handleSizeChange = (dimension, value) => {
		onUpdate({
			data: {
				...data,
				size: {
					...data.size,
					[dimension]: parseInt(value, 10),
				},
			},
		});
	};

	const handleBackgroundChange = (type, value) => {
		onUpdate({
			data: {
				...data,
				background: {
					type,
					value,
				},
			},
		});
	};

	const presetSizes = [
		{ label: __('Letter Landscape (792×612)', 'certbuilder-pro'), value: '792x612' },
		{ label: __('Letter Portrait (612×792)', 'certbuilder-pro'), value: '612x792' },
		{ label: __('A4 Landscape (842×595)', 'certbuilder-pro'), value: '842x595' },
		{ label: __('A4 Portrait (595×842)', 'certbuilder-pro'), value: '595x842' },
		{ label: __('Custom', 'certbuilder-pro'), value: 'custom' },
	];

	const currentSize = `${data.size?.width}x${data.size?.height}`;
	const isPreset = presetSizes.some((s) => s.value === currentSize);

	return (
		<div className="cb-settings-panel">
			<h3>{__('Certificate Settings', 'certbuilder-pro')}</h3>

			<div className="cb-settings-section">
				<h4>{__('Page Size', 'certbuilder-pro')}</h4>

				<SelectControl
					label={__('Preset Size', 'certbuilder-pro')}
					value={isPreset ? currentSize : 'custom'}
					options={presetSizes}
					onChange={(value) => {
						if (value !== 'custom') {
							const [width, height] = value.split('x').map(Number);
							onUpdate({
								data: {
									...data,
									size: { width, height },
									orientation: width > height ? 'landscape' : 'portrait',
								},
							});
						}
					}}
				/>

				<div className="cb-size-inputs">
					<TextControl
						label={__('Width (pt)', 'certbuilder-pro')}
						type="number"
						value={data.size?.width || 792}
						onChange={(value) => handleSizeChange('width', value)}
					/>
					<TextControl
						label={__('Height (pt)', 'certbuilder-pro')}
						type="number"
						value={data.size?.height || 612}
						onChange={(value) => handleSizeChange('height', value)}
					/>
				</div>
			</div>

			<div className="cb-settings-section">
				<h4>{__('Background', 'certbuilder-pro')}</h4>

				<SelectControl
					label={__('Background Type', 'certbuilder-pro')}
					value={data.background?.type || 'color'}
					options={[
						{ label: __('Solid Color', 'certbuilder-pro'), value: 'color' },
						{ label: __('Image', 'certbuilder-pro'), value: 'image' },
					]}
					onChange={(type) => handleBackgroundChange(type, data.background?.value || '#ffffff')}
				/>

				{data.background?.type === 'color' && (
					<div className="cb-color-picker-wrapper">
						<ColorPicker
							color={data.background?.value || '#ffffff'}
							onChange={(color) => handleBackgroundChange('color', color)}
							enableAlpha={false}
						/>
					</div>
				)}

				{data.background?.type === 'image' && (
					<div className="cb-image-upload">
						<TextControl
							label={__('Image URL', 'certbuilder-pro')}
							value={data.background?.value || ''}
							onChange={(url) => handleBackgroundChange('image', url)}
							placeholder="https://..."
						/>
						<button
							className="button"
							onClick={() => {
								// Open WordPress media library
								const frame = wp.media({
									title: __('Select Background Image', 'certbuilder-pro'),
									multiple: false,
									library: { type: 'image' },
								});

								frame.on('select', () => {
									const attachment = frame.state().get('selection').first().toJSON();
									handleBackgroundChange('image', attachment.url);
								});

								frame.open();
							}}
						>
							{__('Select Image', 'certbuilder-pro')}
						</button>
					</div>
				)}
			</div>
		</div>
	);
};

export default SettingsPanel;
