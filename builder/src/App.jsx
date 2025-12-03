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
	const [selectedElements, setSelectedElements] = useState([]);
	const [canvas, setCanvas] = useState(null);

	// For convenience, get the first/only selected element
	const selectedElement = selectedElements.length === 1 ? selectedElements[0] : null;

	const {
		template,
		setTemplate,
		updateTemplate,
		addElement,
		updateElement,
		removeElement,
		duplicateElement,
		moveElementLayer,
		bringToFront,
		sendToBack,
		saveTemplate,
	} = useTemplate(templateId);

	// History now automatically tracks template changes
	const { canUndo, canRedo, undo, redo } = useHistory(template, setTemplate);

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

	// Handle duplicate element(s)
	const handleDuplicateElement = useCallback(() => {
		if (selectedElements.length === 0) return;
		const duplicatedElements = [];
		selectedElements.forEach((el) => {
			const duplicated = duplicateElement(el.id);
			if (duplicated) duplicatedElements.push(duplicated);
		});
		if (duplicatedElements.length > 0) {
			setSelectedElements(duplicatedElements);
		}
	}, [selectedElements, duplicateElement]);

	// Handle layer operations
	const handleBringForward = useCallback(() => {
		if (selectedElements.length === 0) return;
		selectedElements.forEach((el) => moveElementLayer(el.id, 'forward'));
	}, [selectedElements, moveElementLayer]);

	const handleSendBackward = useCallback(() => {
		if (selectedElements.length === 0) return;
		selectedElements.forEach((el) => moveElementLayer(el.id, 'backward'));
	}, [selectedElements, moveElementLayer]);

	const handleBringToFront = useCallback(() => {
		if (selectedElements.length === 0) return;
		selectedElements.forEach((el) => bringToFront(el.id));
	}, [selectedElements, bringToFront]);

	const handleSendToBack = useCallback(() => {
		if (selectedElements.length === 0) return;
		selectedElements.forEach((el) => sendToBack(el.id));
	}, [selectedElements, sendToBack]);

	// Handle keyboard shortcuts
	useEffect(() => {
		const handleKeyDown = (e) => {
			// Ignore when typing in input fields
			if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

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
			// Ctrl/Cmd + D = Duplicate element(s)
			if ((e.ctrlKey || e.metaKey) && e.key === 'd' && selectedElements.length > 0) {
				e.preventDefault();
				handleDuplicateElement();
			}
			// Ctrl/Cmd + ] = Bring forward
			if ((e.ctrlKey || e.metaKey) && e.key === ']' && selectedElements.length > 0) {
				e.preventDefault();
				if (e.shiftKey) {
					handleBringToFront();
				} else {
					handleBringForward();
				}
			}
			// Ctrl/Cmd + [ = Send backward
			if ((e.ctrlKey || e.metaKey) && e.key === '[' && selectedElements.length > 0) {
				e.preventDefault();
				if (e.shiftKey) {
					handleSendToBack();
				} else {
					handleSendBackward();
				}
			}
			// Ctrl/Cmd + A = Select all
			if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
				e.preventDefault();
				if (template?.data?.elements) {
					setSelectedElements([...template.data.elements]);
				}
			}
			// Escape = Deselect all
			if (e.key === 'Escape') {
				setSelectedElements([]);
			}
			// Delete = Remove selected element(s)
			if ((e.key === 'Delete' || e.key === 'Backspace') && selectedElements.length > 0) {
				e.preventDefault();
				selectedElements.forEach((el) => removeElement(el.id));
				setSelectedElements([]);
			}
		};

		window.addEventListener('keydown', handleKeyDown);
		return () => window.removeEventListener('keydown', handleKeyDown);
	}, [handleSave, undo, redo, selectedElements, removeElement, handleDuplicateElement, handleBringForward, handleSendBackward, handleBringToFront, handleSendToBack, template]);

	// Handle element selection (supports multi-select via array)
	const handleElementSelect = useCallback((elements, addToSelection = false) => {
		if (!elements) {
			setSelectedElements([]);
			return;
		}

		const elementsArray = Array.isArray(elements) ? elements : [elements];

		if (addToSelection) {
			setSelectedElements((prev) => {
				const newIds = elementsArray.map((el) => el.id);
				const existing = prev.filter((el) => !newIds.includes(el.id));
				return [...existing, ...elementsArray];
			});
		} else {
			setSelectedElements(elementsArray);
		}
	}, []);

	// Handle element update
	const handleElementUpdate = useCallback(
		(elementId, updates) => {
			updateElement(elementId, updates);
			// Update selected elements if the updated element is in selection
			setSelectedElements((prev) =>
				prev.map((el) => (el.id === elementId ? { ...el, ...updates } : el))
			);
		},
		[updateElement]
	);

	// Handle add element
	const handleAddElement = useCallback(
		(type, options = {}) => {
			const newElement = addElement(type, options);
			setSelectedElements([newElement]);
			return newElement;
		},
		[addElement]
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
						selectedElements={selectedElements}
						onElementSelect={handleElementSelect}
						onElementUpdate={handleElementUpdate}
						onElementDuplicate={handleDuplicateElement}
						onBringForward={handleBringForward}
						onSendBackward={handleSendBackward}
						onBringToFront={handleBringToFront}
						onSendToBack={handleSendToBack}
						onCanvasReady={setCanvas}
					/>
				</div>

				<PropertiesPanel
					elements={selectedElements}
					onUpdate={(updates) => {
						// Apply updates to all selected elements
						selectedElements.forEach((el) => {
							handleElementUpdate(el.id, updates);
						});
					}}
					onDelete={() => {
						selectedElements.forEach((el) => removeElement(el.id));
						setSelectedElements([]);
					}}
				/>
			</div>
		</div>
	);
};

export default App;
