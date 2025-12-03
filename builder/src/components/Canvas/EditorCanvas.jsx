/**
 * Fabric.js Canvas Editor Component
 * Enhanced with snap-to-grid, alignment guides, and grid overlay
 */

import { useEffect, useRef, useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { fabric } from 'fabric';
import Rulers from './Rulers';

const GRID_SIZE = 10;
const SNAP_THRESHOLD = 5;
const GUIDE_COLOR = '#3b82f6';

const EditorCanvas = ({
	template,
	selectedElements = [],
	onElementSelect,
	onElementUpdate,
	onElementDuplicate,
	onBringForward,
	onSendBackward,
	onBringToFront,
	onSendToBack,
	onCanvasReady,
}) => {
	// Track selected IDs for convenience
	const selectedIds = selectedElements.map((el) => el.id);
	const hasSelection = selectedElements.length > 0;
	const canvasRef = useRef(null);
	const fabricRef = useRef(null);
	const guidelinesRef = useRef([]);

	const [zoom, setZoom] = useState(1);
	const [showGrid, setShowGrid] = useState(false);
	const [snapToGrid, setSnapToGrid] = useState(true);
	const [showGuides, setShowGuides] = useState(true);
	const [showRulers, setShowRulers] = useState(true);

	// Initialize canvas
	useEffect(() => {
		if (!canvasRef.current || fabricRef.current) return;

		const size = template?.data?.size || { width: 792, height: 612 };

		const canvas = new fabric.Canvas(canvasRef.current, {
			width: size.width,
			height: size.height,
			backgroundColor: '#ffffff',
			selection: true,
			preserveObjectStacking: true,
			snapAngle: 45,
			snapThreshold: 5,
		});

		fabricRef.current = canvas;

		// Handle selection
		canvas.on('selection:created', handleSelection);
		canvas.on('selection:updated', handleSelection);
		canvas.on('selection:cleared', () => onElementSelect(null));

		// Handle object moving with snapping and guides
		canvas.on('object:moving', handleObjectMoving);
		canvas.on('object:scaling', handleObjectMoving);

		// Clear guidelines when done moving
		canvas.on('object:modified', handleObjectModified);
		canvas.on('mouse:up', clearGuidelines);

		onCanvasReady?.(canvas);

		return () => {
			canvas.dispose();
			fabricRef.current = null;
		};
	}, []);

	// Handle selection (supports multi-select with Shift)
	const handleSelection = useCallback((e) => {
		if (e.selected && e.selected.length > 0) {
			const elements = e.selected
				.map((obj) => obj.elementData)
				.filter(Boolean);
			if (elements.length > 0) {
				// Check if Shift is pressed for additive selection
				const addToSelection = e.e?.shiftKey || false;
				onElementSelect(elements, addToSelection);
			}
		}
	}, [onElementSelect]);

	// Handle object moving with snap and guides
	const handleObjectMoving = useCallback((e) => {
		const canvas = fabricRef.current;
		const obj = e.target;
		if (!canvas || !obj) return;

		let left = obj.left;
		let top = obj.top;

		// Snap to grid
		if (snapToGrid) {
			left = Math.round(left / GRID_SIZE) * GRID_SIZE;
			top = Math.round(top / GRID_SIZE) * GRID_SIZE;
		}

		// Smart alignment guides
		if (showGuides) {
			const guides = calculateAlignmentGuides(canvas, obj);

			// Snap to guides if close enough
			if (guides.vertical !== null && Math.abs(obj.left - guides.vertical) < SNAP_THRESHOLD) {
				left = guides.vertical;
			}
			if (guides.horizontal !== null && Math.abs(obj.top - guides.horizontal) < SNAP_THRESHOLD) {
				top = guides.horizontal;
			}

			// Draw guidelines
			drawGuidelines(canvas, guides, obj);
		}

		obj.set({ left, top });
	}, [snapToGrid, showGuides]);

	// Handle object modified (final position)
	const handleObjectModified = useCallback((e) => {
		const obj = e.target;
		if (!obj?.elementData) return;

		clearGuidelines();

		const updates = {
			x: Math.round(obj.left),
			y: Math.round(obj.top),
			rotation: Math.round(obj.angle || 0),
		};

		if (obj.type !== 'line') {
			updates.width = Math.round(obj.width * obj.scaleX);
			updates.height = Math.round(obj.height * obj.scaleY);
		}

		onElementUpdate(obj.elementData.id, updates);

		// Reset scale after resize
		obj.set({
			scaleX: 1,
			scaleY: 1,
			width: updates.width || obj.width,
			height: updates.height || obj.height,
		});
	}, [onElementUpdate]);

	// Calculate alignment guides based on other objects
	const calculateAlignmentGuides = useCallback((canvas, movingObj) => {
		const guides = {
			vertical: null,
			horizontal: null,
			verticalEdges: [],
			horizontalEdges: [],
		};

		const canvasWidth = canvas.width;
		const canvasHeight = canvas.height;
		const objCenter = movingObj.getCenterPoint();
		const objBounds = movingObj.getBoundingRect();

		// Canvas center guides
		const canvasCenterX = canvasWidth / 2;
		const canvasCenterY = canvasHeight / 2;

		// Check canvas center alignment
		if (Math.abs(objCenter.x - canvasCenterX) < SNAP_THRESHOLD) {
			guides.vertical = canvasCenterX - (objBounds.width / 2);
			guides.verticalEdges.push({ x: canvasCenterX, y1: 0, y2: canvasHeight });
		}
		if (Math.abs(objCenter.y - canvasCenterY) < SNAP_THRESHOLD) {
			guides.horizontal = canvasCenterY - (objBounds.height / 2);
			guides.horizontalEdges.push({ y: canvasCenterY, x1: 0, x2: canvasWidth });
		}

		// Check alignment with other objects
		canvas.getObjects().forEach((obj) => {
			if (obj === movingObj || obj.isGuideline) return;

			const targetCenter = obj.getCenterPoint();
			const targetBounds = obj.getBoundingRect();

			// Vertical center alignment
			if (Math.abs(objCenter.x - targetCenter.x) < SNAP_THRESHOLD) {
				guides.vertical = targetCenter.x - (objBounds.width / 2);
				guides.verticalEdges.push({
					x: targetCenter.x,
					y1: Math.min(objBounds.top, targetBounds.top),
					y2: Math.max(objBounds.top + objBounds.height, targetBounds.top + targetBounds.height),
				});
			}

			// Horizontal center alignment
			if (Math.abs(objCenter.y - targetCenter.y) < SNAP_THRESHOLD) {
				guides.horizontal = targetCenter.y - (objBounds.height / 2);
				guides.horizontalEdges.push({
					y: targetCenter.y,
					x1: Math.min(objBounds.left, targetBounds.left),
					x2: Math.max(objBounds.left + objBounds.width, targetBounds.left + targetBounds.width),
				});
			}

			// Left edge alignment
			if (Math.abs(objBounds.left - targetBounds.left) < SNAP_THRESHOLD) {
				guides.vertical = targetBounds.left;
				guides.verticalEdges.push({
					x: targetBounds.left,
					y1: Math.min(objBounds.top, targetBounds.top),
					y2: Math.max(objBounds.top + objBounds.height, targetBounds.top + targetBounds.height),
				});
			}

			// Right edge alignment
			if (Math.abs(objBounds.left + objBounds.width - (targetBounds.left + targetBounds.width)) < SNAP_THRESHOLD) {
				guides.vertical = targetBounds.left + targetBounds.width - objBounds.width;
				guides.verticalEdges.push({
					x: targetBounds.left + targetBounds.width,
					y1: Math.min(objBounds.top, targetBounds.top),
					y2: Math.max(objBounds.top + objBounds.height, targetBounds.top + targetBounds.height),
				});
			}

			// Top edge alignment
			if (Math.abs(objBounds.top - targetBounds.top) < SNAP_THRESHOLD) {
				guides.horizontal = targetBounds.top;
				guides.horizontalEdges.push({
					y: targetBounds.top,
					x1: Math.min(objBounds.left, targetBounds.left),
					x2: Math.max(objBounds.left + objBounds.width, targetBounds.left + targetBounds.width),
				});
			}

			// Bottom edge alignment
			if (Math.abs(objBounds.top + objBounds.height - (targetBounds.top + targetBounds.height)) < SNAP_THRESHOLD) {
				guides.horizontal = targetBounds.top + targetBounds.height - objBounds.height;
				guides.horizontalEdges.push({
					y: targetBounds.top + targetBounds.height,
					x1: Math.min(objBounds.left, targetBounds.left),
					x2: Math.max(objBounds.left + objBounds.width, targetBounds.left + targetBounds.width),
				});
			}
		});

		return guides;
	}, []);

	// Draw alignment guidelines
	const drawGuidelines = useCallback((canvas, guides) => {
		clearGuidelines();

		// Draw vertical guides
		guides.verticalEdges.forEach((edge) => {
			const line = new fabric.Line([edge.x, edge.y1, edge.x, edge.y2], {
				stroke: GUIDE_COLOR,
				strokeWidth: 1,
				strokeDashArray: [5, 5],
				selectable: false,
				evented: false,
				isGuideline: true,
			});
			guidelinesRef.current.push(line);
			canvas.add(line);
		});

		// Draw horizontal guides
		guides.horizontalEdges.forEach((edge) => {
			const line = new fabric.Line([edge.x1, edge.y, edge.x2, edge.y], {
				stroke: GUIDE_COLOR,
				strokeWidth: 1,
				strokeDashArray: [5, 5],
				selectable: false,
				evented: false,
				isGuideline: true,
			});
			guidelinesRef.current.push(line);
			canvas.add(line);
		});

		canvas.renderAll();
	}, []);

	// Clear guidelines
	const clearGuidelines = useCallback(() => {
		const canvas = fabricRef.current;
		if (!canvas) return;

		guidelinesRef.current.forEach((line) => {
			canvas.remove(line);
		});
		guidelinesRef.current = [];
		canvas.renderAll();
	}, []);

	// Draw grid overlay
	const drawGrid = useCallback(() => {
		const canvas = fabricRef.current;
		if (!canvas) return;

		// Remove existing grid
		canvas.getObjects().forEach((obj) => {
			if (obj.isGrid) canvas.remove(obj);
		});

		if (!showGrid) {
			canvas.renderAll();
			return;
		}

		const width = canvas.width;
		const height = canvas.height;

		// Draw vertical lines
		for (let x = GRID_SIZE; x < width; x += GRID_SIZE) {
			const line = new fabric.Line([x, 0, x, height], {
				stroke: x % 50 === 0 ? '#ddd' : '#eee',
				strokeWidth: 1,
				selectable: false,
				evented: false,
				isGrid: true,
			});
			canvas.add(line);
			canvas.sendToBack(line);
		}

		// Draw horizontal lines
		for (let y = GRID_SIZE; y < height; y += GRID_SIZE) {
			const line = new fabric.Line([0, y, width, y], {
				stroke: y % 50 === 0 ? '#ddd' : '#eee',
				strokeWidth: 1,
				selectable: false,
				evented: false,
				isGrid: true,
			});
			canvas.add(line);
			canvas.sendToBack(line);
		}

		canvas.renderAll();
	}, [showGrid]);

	// Toggle grid
	useEffect(() => {
		drawGrid();
	}, [showGrid, drawGrid]);

	// Update canvas when template changes
	useEffect(() => {
		const canvas = fabricRef.current;
		if (!canvas || !template?.data) return;

		// Clear canvas (keep grid)
		canvas.getObjects().forEach((obj) => {
			if (!obj.isGrid) canvas.remove(obj);
		});

		// Set background
		const bg = template.data.background;
		if (bg?.type === 'color') {
			canvas.setBackgroundColor(bg.value, canvas.renderAll.bind(canvas));
		} else if (bg?.type === 'image' && bg.value) {
			fabric.Image.fromURL(bg.value, (img) => {
				canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
					scaleX: canvas.width / img.width,
					scaleY: canvas.height / img.height,
				});
			});
		}

		// Add elements
		const elements = template.data.elements || [];
		elements.forEach((element) => {
			addElementToCanvas(canvas, element);
		});

		// Redraw grid on top layer management
		if (showGrid) drawGrid();

		canvas.renderAll();
	}, [template?.data, drawGrid, showGrid]);

	// Highlight selected elements
	useEffect(() => {
		const canvas = fabricRef.current;
		if (!canvas) return;

		if (selectedIds.length === 0) {
			canvas.discardActiveObject();
		} else if (selectedIds.length === 1) {
			const obj = canvas.getObjects().find((o) => o.elementData?.id === selectedIds[0]);
			if (obj) {
				canvas.setActiveObject(obj);
			}
		} else {
			// Multi-select: create active selection
			const objectsToSelect = canvas.getObjects().filter((obj) =>
				obj.elementData && selectedIds.includes(obj.elementData.id)
			);
			if (objectsToSelect.length > 1) {
				const selection = new fabric.ActiveSelection(objectsToSelect, { canvas });
				canvas.setActiveObject(selection);
			}
		}

		canvas.renderAll();
	}, [selectedIds.join(',')]);

	// Zoom controls
	const handleZoom = useCallback((delta) => {
		const newZoom = Math.max(0.25, Math.min(2, zoom + delta));
		setZoom(newZoom);
	}, [zoom]);

	// Center selected object
	const centerObject = useCallback((axis) => {
		const canvas = fabricRef.current;
		const activeObj = canvas?.getActiveObject();
		if (!activeObj) return;

		if (axis === 'horizontal') {
			activeObj.set({ left: (canvas.width - activeObj.width * activeObj.scaleX) / 2 });
		} else if (axis === 'vertical') {
			activeObj.set({ top: (canvas.height - activeObj.height * activeObj.scaleY) / 2 });
		} else {
			activeObj.center();
		}

		canvas.renderAll();
		handleObjectModified({ target: activeObj });
	}, [handleObjectModified]);

	return (
		<div className="cb-canvas-wrapper">
			<div className="cb-canvas-controls">
				{/* Zoom controls */}
				<div className="cb-control-group">
					<button
						className="cb-zoom-btn"
						onClick={() => handleZoom(-0.1)}
						title={__('Zoom Out', 'certbuilder-pro')}
					>
						−
					</button>
					<span className="cb-zoom-level">{Math.round(zoom * 100)}%</span>
					<button
						className="cb-zoom-btn"
						onClick={() => handleZoom(0.1)}
						title={__('Zoom In', 'certbuilder-pro')}
					>
						+
					</button>
					<button
						className="cb-zoom-btn"
						onClick={() => setZoom(1)}
						title={__('Reset Zoom', 'certbuilder-pro')}
					>
						100%
					</button>
				</div>

				<div className="cb-control-divider" />

				{/* Grid & Snap controls */}
				<div className="cb-control-group">
					<button
						className={`cb-toggle-btn ${showRulers ? 'active' : ''}`}
						onClick={() => setShowRulers(!showRulers)}
						title={__('Toggle Rulers', 'certbuilder-pro')}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M0 0h16v4H0V0zm0 0v16h4V0H0z" opacity="0.3"/>
							<path d="M2 6h1v1H2zm0 2h1v1H2zm0 2h1v1H2zm0 2h1v1H2zM6 2h1v1H6zm2 0h1v1H8zm2 0h1v1h-1zm2 0h1v1h-1z"/>
						</svg>
					</button>
					<button
						className={`cb-toggle-btn ${showGrid ? 'active' : ''}`}
						onClick={() => setShowGrid(!showGrid)}
						title={__('Toggle Grid', 'certbuilder-pro')}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M0 0h4v4H0V0zm6 0h4v4H6V0zm6 0h4v4h-4V0zM0 6h4v4H0V6zm6 0h4v4H6V6zm6 0h4v4h-4V6zM0 12h4v4H0v-4zm6 0h4v4H6v-4zm6 0h4v4h-4v-4z"/>
						</svg>
					</button>
					<button
						className={`cb-toggle-btn ${snapToGrid ? 'active' : ''}`}
						onClick={() => setSnapToGrid(!snapToGrid)}
						title={__('Snap to Grid', 'certbuilder-pro')}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M14 1H2a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V2a1 1 0 00-1-1zM2 0a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V2a2 2 0 00-2-2H2z"/>
							<path d="M8 4a.5.5 0 01.5.5v7a.5.5 0 01-1 0v-7A.5.5 0 018 4z"/>
							<path d="M4 8a.5.5 0 01.5-.5h7a.5.5 0 010 1h-7A.5.5 0 014 8z"/>
						</svg>
					</button>
					<button
						className={`cb-toggle-btn ${showGuides ? 'active' : ''}`}
						onClick={() => setShowGuides(!showGuides)}
						title={__('Smart Guides', 'certbuilder-pro')}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M8 0v16M0 8h16" stroke="currentColor" strokeWidth="1" strokeDasharray="2,2"/>
						</svg>
					</button>
				</div>

				<div className="cb-control-divider" />

				{/* Alignment controls */}
				<div className="cb-control-group">
					<button
						className="cb-align-btn"
						onClick={() => centerObject('horizontal')}
						title={__('Center Horizontally', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M8 1v14M3 4h4v3H3zM9 4h4v3H9zM4 9h3v3H4zM9 9h3v3h-3z"/>
						</svg>
					</button>
					<button
						className="cb-align-btn"
						onClick={() => centerObject('vertical')}
						title={__('Center Vertically', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M1 8h14M4 3v4h3V3zM4 9v4h3V9zM9 4v3h3V4zM9 9v3h3V9z"/>
						</svg>
					</button>
					<button
						className="cb-align-btn"
						onClick={() => centerObject('both')}
						title={__('Center on Canvas', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						⊙
					</button>
				</div>

				<div className="cb-control-divider" />

				{/* Duplicate control */}
				<div className="cb-control-group">
					<button
						className="cb-action-btn"
						onClick={onElementDuplicate}
						title={__('Duplicate (Ctrl+D)', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M4 2a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V2z"/>
							<path d="M2 6a2 2 0 012-2v8a2 2 0 002 2h6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
						</svg>
					</button>
				</div>

				<div className="cb-control-divider" />

				{/* Layer controls */}
				<div className="cb-control-group">
					<button
						className="cb-layer-btn"
						onClick={onSendToBack}
						title={__('Send to Back (Ctrl+Shift+[)', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M2 2h5v5H2z" opacity="0.3"/>
							<path d="M9 9h5v5H9z"/>
						</svg>
					</button>
					<button
						className="cb-layer-btn"
						onClick={onSendBackward}
						title={__('Send Backward (Ctrl+[)', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M8 4l-4 4h8z"/>
							<path d="M4 9h8v2H4z"/>
						</svg>
					</button>
					<button
						className="cb-layer-btn"
						onClick={onBringForward}
						title={__('Bring Forward (Ctrl+])', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M4 5h8v2H4z"/>
							<path d="M8 12l4-4H4z"/>
						</svg>
					</button>
					<button
						className="cb-layer-btn"
						onClick={onBringToFront}
						title={__('Bring to Front (Ctrl+Shift+])', 'certbuilder-pro')}
						disabled={!hasSelection}
					>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
							<path d="M2 2h5v5H2z"/>
							<path d="M9 9h5v5H9z" opacity="0.3"/>
						</svg>
					</button>
				</div>
			</div>

			<div className="cb-canvas-with-rulers">
				<Rulers
					width={template?.data?.size?.width || 792}
					height={template?.data?.size?.height || 612}
					zoom={zoom}
					showRulers={showRulers}
				/>

				<div
					className="cb-canvas-scroll"
					style={{
						transform: `scale(${zoom})`,
						transformOrigin: 'top left',
						marginLeft: showRulers ? 20 : 0,
						marginTop: showRulers ? 20 : 0,
					}}
				>
					<canvas ref={canvasRef} />
				</div>
			</div>
		</div>
	);
};

// Add element to Fabric canvas
function addElementToCanvas(canvas, element) {
	let obj;

	switch (element.type) {
		case 'text':
		case 'dynamic_field':
			obj = new fabric.Textbox(element.content || `{{${element.field}}}`, {
				left: element.x,
				top: element.y,
				fontSize: element.fontSize || 24,
				fontFamily: getFontFamily(element.fontFamily),
				fontWeight: element.fontWeight || 'normal',
				fontStyle: element.fontStyle || 'normal',
				fill: element.fill || '#000000',
				textAlign: element.align || 'left',
				width: element.width || 200,
				angle: element.rotation || 0,
			});
			break;

		case 'image':
			if (element.src) {
				fabric.Image.fromURL(element.src, (img) => {
					img.set({
						left: element.x,
						top: element.y,
						scaleX: element.width / img.width,
						scaleY: element.height / img.height,
						angle: element.rotation || 0,
					});
					img.elementData = element;
					canvas.add(img);
					canvas.renderAll();
				});
				return;
			}
			break;

		case 'shape':
			if (element.shapeType === 'circle') {
				obj = new fabric.Circle({
					left: element.x,
					top: element.y,
					radius: Math.min(element.width, element.height) / 2,
					fill: element.fill || '#3b82f6',
					stroke: element.stroke || '',
					strokeWidth: element.strokeWidth || 0,
					angle: element.rotation || 0,
				});
			} else {
				obj = new fabric.Rect({
					left: element.x,
					top: element.y,
					width: element.width || 100,
					height: element.height || 100,
					fill: element.fill || '#3b82f6',
					stroke: element.stroke || '',
					strokeWidth: element.strokeWidth || 0,
					angle: element.rotation || 0,
				});
			}
			break;

		case 'line':
			obj = new fabric.Line(
				[0, 0, element.width || 200, 0],
				{
					left: element.x,
					top: element.y,
					stroke: element.stroke || '#000000',
					strokeWidth: element.strokeWidth || 2,
					angle: element.rotation || 0,
				}
			);
			break;

		case 'qr_code':
			const qrSize = element.size || 80;
			const rect = new fabric.Rect({
				width: qrSize,
				height: qrSize,
				fill: '#ffffff',
				stroke: '#000000',
				strokeWidth: 1,
			});

			const label = new fabric.Text('QR', {
				fontSize: 16,
				originX: 'center',
				originY: 'center',
				left: qrSize / 2,
				top: qrSize / 2,
				fill: '#666666',
			});

			obj = new fabric.Group([rect, label], {
				left: element.x,
				top: element.y,
			});
			break;

		default:
			return;
	}

	if (obj) {
		obj.elementData = element;
		canvas.add(obj);
	}
}

// Get font family name for CSS
function getFontFamily(fontId) {
	const fontMap = {
		'playfair-display': '"Playfair Display", serif',
		'lora': '"Lora", serif',
		'merriweather': '"Merriweather", serif',
		'montserrat': '"Montserrat", sans-serif',
		'open-sans': '"Open Sans", sans-serif',
		'raleway': '"Raleway", sans-serif',
		'roboto': '"Roboto", sans-serif',
		'lato': '"Lato", sans-serif',
		'poppins': '"Poppins", sans-serif',
		'great-vibes': '"Great Vibes", cursive',
		'dancing-script': '"Dancing Script", cursive',
		'pacifico': '"Pacifico", cursive',
		'alex-brush': '"Alex Brush", cursive',
		'allura': '"Allura", cursive',
		'cinzel': '"Cinzel", serif',
	};

	return fontMap[fontId] || fontId;
}

export default EditorCanvas;
