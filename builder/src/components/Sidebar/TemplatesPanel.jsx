/**
 * Templates Panel Component
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const TemplatesPanel = () => {
	const [templates, setTemplates] = useState([]);
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		const fetchTemplates = async () => {
			try {
				const response = await apiFetch({
					path: '/certbuilder/v1/templates',
				});
				setTemplates(response);
			} catch (error) {
				console.error('Error fetching templates:', error);
			}
			setLoading(false);
		};

		fetchTemplates();
	}, []);

	const prebuiltTemplates = [
		{
			id: 'modern-minimal',
			name: __('Modern Minimal', 'certbuilder-pro'),
			preview: null,
		},
		{
			id: 'classic-elegant',
			name: __('Classic Elegant', 'certbuilder-pro'),
			preview: null,
		},
		{
			id: 'corporate-professional',
			name: __('Corporate Professional', 'certbuilder-pro'),
			preview: null,
		},
		{
			id: 'academic-traditional',
			name: __('Academic Traditional', 'certbuilder-pro'),
			preview: null,
		},
	];

	return (
		<div className="cb-templates-panel">
			<h3>{__('Start from Template', 'certbuilder-pro')}</h3>

			<div className="cb-templates-section">
				<h4>{__('Pre-built Templates', 'certbuilder-pro')}</h4>
				<div className="cb-templates-grid">
					{prebuiltTemplates.map((template) => (
						<div key={template.id} className="cb-template-item">
							<div className="cb-template-preview">
								{template.preview ? (
									<img src={template.preview} alt={template.name} />
								) : (
									<div className="cb-template-placeholder">
										<span>📄</span>
									</div>
								)}
							</div>
							<span className="cb-template-name">{template.name}</span>
						</div>
					))}
				</div>
			</div>

			{templates.length > 0 && (
				<div className="cb-templates-section">
					<h4>{__('Your Templates', 'certbuilder-pro')}</h4>
					{loading ? (
						<p>{__('Loading...', 'certbuilder-pro')}</p>
					) : (
						<div className="cb-templates-grid">
							{templates.map((template) => (
								<a
									key={template.id}
									href={`admin.php?page=certbuilder-builder&template_id=${template.id}`}
									className="cb-template-item"
								>
									<div className="cb-template-preview">
										{template.thumbnail ? (
											<img src={template.thumbnail} alt={template.title} />
										) : (
											<div className="cb-template-placeholder">
												<span>📄</span>
											</div>
										)}
									</div>
									<span className="cb-template-name">{template.title}</span>
								</a>
							))}
						</div>
					)}
				</div>
			)}
		</div>
	);
};

export default TemplatesPanel;
