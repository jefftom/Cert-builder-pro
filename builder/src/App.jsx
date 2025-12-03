/**
 * Main App Component
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import EditorCanvas from './components/Canvas/EditorCanvas';
import Sidebar from './components/Sidebar/Sidebar';
import Toolbar from './components/Toolbar/Toolbar';
import PropertiesPanel from './components/Properties/PropertiesPanel';
import { useTemplate } from './hooks/useTemplate';
import { useHistory } from './hooks/useHistory';

const App = () => {
	const templateId = window.certbuilderBuilder?.templateId || 0;
	const [loading, setLoading] = useState(true);
	const [saving, setSaving] = useState(false);
	const [selectedElement, setSelectedElement] = useState(null);
	const [canvas, setCanvas] = useState(null);

	const {
		template,
		setTemplate,
		updateTemplate,
		addElement,
		updateElement,
		removeElement,
		saveTemplate,
	} = useTemplate(templateId);

	const { canUndo, canRedo, undo, redo, pushState } = useHistory(template);

	// Load template
	useEffect(() => {
		const loadTemplate = async () => {
			if (templateId) {
				try {
					const response = await apiFetch({
						path: `/certbuilder/v1/templates/${templateId}`,
					});
					setTemplate(response);
				} catch (error) {
					console.error('Error loading template:', error);
				}
			} else {
				// New template with defaults
				setTemplate({
					id: 0,
					title: __('New Certificate', 'certbuilder-pro'),
					data: {
						name: __('New Certificate', 'certbuilder-pro'),
						size: { width: 792, height: 612 },
						orientation: 'landscape',
						background: { type: 'color', value: '#ffffff' },
						elements: [],
					},
				});
			}
			setLoading(false);
		};

		loadTemplate();
	}, [templateId, setTemplate]);

	// Handle save
	const handleSave = useCallback(async () => {
		setSaving(true);
		try {
			const savedTemplate = await saveTemplate();
			if (savedTemplate && !templateId) {
				// Redirect to edit URL for new templates
				window.location.href = `admin.php?page=certbuilder-builder&template_id=${savedTemplate.id}`;
			}
		} catch (error) {
			console.error('Error saving template:', error);
			alert(__('Error saving template. Please try again.', 'certbuilder-pro'));
		}
		setSaving(false);
	}, [saveTemplate, templateId]);

	// Handle keyboard shortcuts
	useEffect(() => {
		const handleKeyDown = (e) => {
			// Ctrl/Cmd + S = Save
			if ((e.ctrlKey || e.metaKey) && e.key === 's') {
				e.preventDefault();
				handleSave();
			}
			// Ctrl/Cmd + Z = Undo
			if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
				e.preventDefault();
				undo();
			}
			// Ctrl/Cmd + Shift + Z = Redo
			if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') {
				e.preventDefault();
				redo();
			}
			// Delete = Remove element
			if ((e.key === 'Delete' || e.key === 'Backspace') && selectedElement) {
				e.preventDefault();
				removeElement(selectedElement.id);
				setSelectedElement(null);
			}
		};

		window.addEventListener('keydown', handleKeyDown);
		return () => window.removeEventListener('keydown', handleKeyDown);
	}, [handleSave, undo, redo, selectedElement, removeElement]);

	// Handle element selection
	const handleElementSelect = useCallback((element) => {
		setSelectedElement(element);
	}, []);

	// Handle element update
	const handleElementUpdate = useCallback(
		(elementId, updates) => {
			updateElement(elementId, updates);
			if (selectedElement && selectedElement.id === elementId) {
				setSelectedElement({ ...selectedElement, ...updates });
			}
			pushState(template);
		},
		[updateElement, selectedElement, pushState, template]
	);

	// Handle add element
	const handleAddElement = useCallback(
		(type, options = {}) => {
			const newElement = addElement(type, options);
			setSelectedElement(newElement);
			pushState(template);
			return newElement;
		},
		[addElement, pushState, template]
	);

	if (loading) {
		return (
			<div className="cb-builder-loading">
				<span className="spinner is-active" />
				<p>{__('Loading builder...', 'certbuilder-pro')}</p>
			</div>
		);
	}

	return (
		<div className="cb-builder">
			<Toolbar
				template={template}
				onSave={handleSave}
				saving={saving}
				canUndo={canUndo}
				canRedo={canRedo}
				onUndo={undo}
				onRedo={redo}
				onTitleChange={(title) => updateTemplate({ title })}
			/>

			<div className="cb-builder-main">
				<Sidebar
					onAddElement={handleAddElement}
					template={template}
					onTemplateUpdate={updateTemplate}
				/>

				<div className="cb-builder-canvas-container">
					<EditorCanvas
						template={template}
						selectedElement={selectedElement}
						onElementSelect={handleElementSelect}
						onElementUpdate={handleElementUpdate}
						onCanvasReady={setCanvas}
					/>
				</div>

				<PropertiesPanel
					element={selectedElement}
					onUpdate={(updates) => {
						if (selectedElement) {
							handleElementUpdate(selectedElement.id, updates);
						}
					}}
					onDelete={() => {
						if (selectedElement) {
							removeElement(selectedElement.id);
							setSelectedElement(null);
						}
					}}
				/>
			</div>
		</div>
	);
};

export default App;
