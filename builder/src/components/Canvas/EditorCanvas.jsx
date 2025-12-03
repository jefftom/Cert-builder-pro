/**
 * Fabric.js Canvas Editor Component
 */

import { useEffect, useRef, useCallback, useState } from '@wordpress/element';
import { fabric } from 'fabric';

const EditorCanvas = ({
	template,
	selectedElement,
	onElementSelect,
	onElementUpdate,
	onCanvasReady,
}) => {
	const canvasRef = useRef(null);
	const fabricRef = useRef(null);
	const [zoom, setZoom] = useState(1);

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
		});

		fabricRef.current = canvas;

		// Handle selection
		canvas.on('selection:created', (e) => {
			if (e.selected?.[0]) {
				const element = e.selected[0].elementData;
				if (element) {
					onElementSelect(element);
				}
			}
		});

		canvas.on('selection:updated', (e) => {
			if (e.selected?.[0]) {
				const element = e.selected[0].elementData;
				if (element) {
					onElementSelect(element);
				}
			}
		});

		canvas.on('selection:cleared', () => {
			onElementSelect(null);
		});

		// Handle object modifications
		canvas.on('object:modified', (e) => {
			const obj = e.target;
			if (obj?.elementData) {
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
			}
		});

		onCanvasReady?.(canvas);

		return () => {
			canvas.dispose();
			fabricRef.current = null;
		};
	}, []);

	// Update canvas when template changes
	useEffect(() => {
		const canvas = fabricRef.current;
		if (!canvas || !template?.data) return;

		// Clear canvas
		canvas.clear();

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

		canvas.renderAll();
	}, [template?.data]);

	// Highlight selected element
	useEffect(() => {
		const canvas = fabricRef.current;
		if (!canvas) return;

		canvas.getObjects().forEach((obj) => {
			if (obj.elementData?.id === selectedElement?.id) {
				canvas.setActiveObject(obj);
			}
		});

		canvas.renderAll();
	}, [selectedElement?.id]);

	// Zoom controls
	const handleZoom = useCallback((delta) => {
		const newZoom = Math.max(0.25, Math.min(2, zoom + delta));
		setZoom(newZoom);
	}, [zoom]);

	return (
		<div className="cb-canvas-wrapper">
			<div className="cb-canvas-controls">
				<button
					className="cb-zoom-btn"
					onClick={() => handleZoom(-0.1)}
					title="Zoom Out"
				>
					−
				</button>
				<span className="cb-zoom-level">{Math.round(zoom * 100)}%</span>
				<button
					className="cb-zoom-btn"
					onClick={() => handleZoom(0.1)}
					title="Zoom In"
				>
					+
				</button>
				<button
					className="cb-zoom-btn"
					onClick={() => setZoom(1)}
					title="Reset Zoom"
				>
					100%
				</button>
			</div>

			<div
				className="cb-canvas-scroll"
				style={{
					transform: `scale(${zoom})`,
					transformOrigin: 'center top',
				}}
			>
				<canvas ref={canvasRef} />
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
				return; // Early return for async image loading
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
			// Placeholder for QR code
			obj = new fabric.Rect({
				left: element.x,
				top: element.y,
				width: element.size || 80,
				height: element.size || 80,
				fill: '#ffffff',
				stroke: '#000000',
				strokeWidth: 1,
			});

			// Add QR label
			const label = new fabric.Text('QR', {
				left: element.x + (element.size || 80) / 2,
				top: element.y + (element.size || 80) / 2,
				fontSize: 16,
				originX: 'center',
				originY: 'center',
				fill: '#666666',
			});

			obj = new fabric.Group([obj, label], {
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
