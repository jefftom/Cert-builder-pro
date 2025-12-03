/**
 * Sidebar Component
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ElementsPanel from './ElementsPanel';
import FieldsPanel from './FieldsPanel';
import TemplatesPanel from './TemplatesPanel';
import SettingsPanel from './SettingsPanel';

const Sidebar = ({ onAddElement, template, onTemplateUpdate }) => {
	const [activeTab, setActiveTab] = useState('elements');

	const tabs = [
		{ id: 'elements', label: __('Elements', 'certbuilder-pro'), icon: '⊞' },
		{ id: 'fields', label: __('Fields', 'certbuilder-pro'), icon: '⚡' },
		{ id: 'templates', label: __('Templates', 'certbuilder-pro'), icon: '📄' },
		{ id: 'settings', label: __('Settings', 'certbuilder-pro'), icon: '⚙' },
	];

	return (
		<div className="cb-sidebar">
			<div className="cb-sidebar-tabs">
				{tabs.map((tab) => (
					<button
						key={tab.id}
						className={`cb-sidebar-tab ${activeTab === tab.id ? 'active' : ''}`}
						onClick={() => setActiveTab(tab.id)}
						title={tab.label}
					>
						<span className="cb-tab-icon">{tab.icon}</span>
						<span className="cb-tab-label">{tab.label}</span>
					</button>
				))}
			</div>

			<div className="cb-sidebar-content">
				{activeTab === 'elements' && (
					<ElementsPanel onAddElement={onAddElement} />
				)}
				{activeTab === 'fields' && (
					<FieldsPanel onAddField={(field) => onAddElement('dynamic_field', { field })} />
				)}
				{activeTab === 'templates' && <TemplatesPanel />}
				{activeTab === 'settings' && (
					<SettingsPanel template={template} onUpdate={onTemplateUpdate} />
				)}
			</div>
		</div>
	);
};

export default Sidebar;
