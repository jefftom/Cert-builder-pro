/**
 * Template management hook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const generateId = () => `el_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

export const useTemplate = (templateId) => {
	const [template, setTemplate] = useState(null);

	// Update template properties
	const updateTemplate = useCallback((updates) => {
		setTemplate((prev) => {
			if (!prev) return prev;
			return {
				...prev,
				...updates,
				data: updates.data ? { ...prev.data, ...updates.data } : prev.data,
			};
		});
	}, []);

	// Add element to template
	const addElement = useCallback((type, options = {}) => {
		const newElement = createDefaultElement(type, options);

		setTemplate((prev) => {
			if (!prev) return prev;
			return {
				...prev,
				data: {
					...prev.data,
					elements: [...(prev.data.elements || []), newElement],
				},
			};
		});

		return newElement;
	}, []);

	// Update element
	const updateElement = useCallback((elementId, updates) => {
		setTemplate((prev) => {
			if (!prev) return prev;
			return {
				...prev,
				data: {
					...prev.data,
					elements: prev.data.elements.map((el) =>
						el.id === elementId ? { ...el, ...updates } : el
					),
				},
			};
		});
	}, []);

	// Remove element
	const removeElement = useCallback((elementId) => {
		setTemplate((prev) => {
			if (!prev) return prev;
			return {
				...prev,
				data: {
					...prev.data,
					elements: prev.data.elements.filter((el) => el.id !== elementId),
				},
			};
		});
	}, []);

	// Save template
	const saveTemplate = useCallback(async () => {
		if (!template) return null;

		const method = template.id ? 'PUT' : 'POST';
		const path = template.id
			? `/certbuilder/v1/templates/${template.id}`
			: '/certbuilder/v1/templates';

		try {
			const response = await apiFetch({
				path,
				method,
				data: {
					title: template.title,
					data: template.data,
				},
			});
			setTemplate(response);
			return response;
		} catch (error) {
			throw error;
		}
	}, [template]);

	return {
		template,
		setTemplate,
		updateTemplate,
		addElement,
		updateElement,
		removeElement,
		saveTemplate,
	};
};

// Create default element based on type
function createDefaultElement(type, options = {}) {
	const id = generateId();
	const baseElement = {
		id,
		type,
		x: options.x || 100,
		y: options.y || 100,
		rotation: 0,
		layerOrder: Date.now(),
	};

	switch (type) {
		case 'text':
			return {
				...baseElement,
				content: options.content || __('Text', 'certbuilder-pro'),
				fontSize: 24,
				fontFamily: 'open-sans',
				fontWeight: 'normal',
				fontStyle: 'normal',
				fill: '#000000',
				align: 'left',
				width: 200,
			};

		case 'dynamic_field':
			return {
				...baseElement,
				field: options.field || 'student_name',
				fontSize: 28,
				fontFamily: 'great-vibes',
				fontWeight: 'normal',
				fontStyle: 'normal',
				fill: '#2c3e50',
				align: 'center',
				width: 300,
				prefix: '',
				suffix: '',
			};

		case 'image':
			return {
				...baseElement,
				src: options.src || '',
				width: options.width || 150,
				height: options.height || 150,
			};

		case 'shape':
			return {
				...baseElement,
				shapeType: options.shapeType || 'rectangle',
				width: 100,
				height: 100,
				fill: '#3b82f6',
				stroke: '',
				strokeWidth: 0,
			};

		case 'line':
			return {
				...baseElement,
				type: 'line',
				width: 200,
				stroke: '#000000',
				strokeWidth: 2,
			};

		case 'qr_code':
			return {
				...baseElement,
				size: 80,
				width: 80,
				height: 80,
			};

		default:
			return baseElement;
	}
}

export default useTemplate;
