/**
 * Rulers Component
 * Displays horizontal and vertical rulers around the canvas
 */

import { useRef, useEffect } from '@wordpress/element';

const RULER_SIZE = 20;
const MAJOR_TICK = 50;
const MINOR_TICK = 10;

const Rulers = ({ width, height, zoom = 1, showRulers = true }) => {
	const horizontalRef = useRef(null);
	const verticalRef = useRef(null);

	useEffect(() => {
		if (!showRulers) return;

		drawHorizontalRuler();
		drawVerticalRuler();
	}, [width, height, zoom, showRulers]);

	const drawHorizontalRuler = () => {
		const canvas = horizontalRef.current;
		if (!canvas) return;

		const ctx = canvas.getContext('2d');
		const scaledWidth = width * zoom;

		canvas.width = scaledWidth;
		canvas.height = RULER_SIZE;

		ctx.fillStyle = '#f8fafc';
		ctx.fillRect(0, 0, scaledWidth, RULER_SIZE);

		ctx.strokeStyle = '#e2e8f0';
		ctx.lineWidth = 1;
		ctx.beginPath();
		ctx.moveTo(0, RULER_SIZE - 0.5);
		ctx.lineTo(scaledWidth, RULER_SIZE - 0.5);
		ctx.stroke();

		ctx.fillStyle = '#64748b';
		ctx.font = '9px system-ui, sans-serif';
		ctx.textAlign = 'center';

		for (let x = 0; x <= width; x += MINOR_TICK) {
			const scaledX = x * zoom;
			const isMajor = x % MAJOR_TICK === 0;

			ctx.strokeStyle = isMajor ? '#94a3b8' : '#cbd5e1';
			ctx.beginPath();
			ctx.moveTo(scaledX, isMajor ? RULER_SIZE - 12 : RULER_SIZE - 6);
			ctx.lineTo(scaledX, RULER_SIZE);
			ctx.stroke();

			if (isMajor && x > 0) {
				ctx.fillText(x.toString(), scaledX, RULER_SIZE - 14);
			}
		}
	};

	const drawVerticalRuler = () => {
		const canvas = verticalRef.current;
		if (!canvas) return;

		const ctx = canvas.getContext('2d');
		const scaledHeight = height * zoom;

		canvas.width = RULER_SIZE;
		canvas.height = scaledHeight;

		ctx.fillStyle = '#f8fafc';
		ctx.fillRect(0, 0, RULER_SIZE, scaledHeight);

		ctx.strokeStyle = '#e2e8f0';
		ctx.lineWidth = 1;
		ctx.beginPath();
		ctx.moveTo(RULER_SIZE - 0.5, 0);
		ctx.lineTo(RULER_SIZE - 0.5, scaledHeight);
		ctx.stroke();

		ctx.fillStyle = '#64748b';
		ctx.font = '9px system-ui, sans-serif';
		ctx.textAlign = 'center';

		for (let y = 0; y <= height; y += MINOR_TICK) {
			const scaledY = y * zoom;
			const isMajor = y % MAJOR_TICK === 0;

			ctx.strokeStyle = isMajor ? '#94a3b8' : '#cbd5e1';
			ctx.beginPath();
			ctx.moveTo(isMajor ? RULER_SIZE - 12 : RULER_SIZE - 6, scaledY);
			ctx.lineTo(RULER_SIZE, scaledY);
			ctx.stroke();

			if (isMajor && y > 0) {
				ctx.save();
				ctx.translate(RULER_SIZE - 14, scaledY);
				ctx.rotate(-Math.PI / 2);
				ctx.fillText(y.toString(), 0, 0);
				ctx.restore();
			}
		}
	};

	if (!showRulers) return null;

	return (
		<>
			{/* Corner box */}
			<div
				className="cb-ruler-corner"
				style={{
					position: 'absolute',
					top: 0,
					left: 0,
					width: RULER_SIZE,
					height: RULER_SIZE,
					background: '#f8fafc',
					borderRight: '1px solid #e2e8f0',
					borderBottom: '1px solid #e2e8f0',
					zIndex: 3,
				}}
			/>

			{/* Horizontal ruler */}
			<div
				className="cb-ruler-horizontal"
				style={{
					position: 'absolute',
					top: 0,
					left: RULER_SIZE,
					width: width * zoom,
					height: RULER_SIZE,
					overflow: 'hidden',
					zIndex: 2,
				}}
			>
				<canvas ref={horizontalRef} />
			</div>

			{/* Vertical ruler */}
			<div
				className="cb-ruler-vertical"
				style={{
					position: 'absolute',
					top: RULER_SIZE,
					left: 0,
					width: RULER_SIZE,
					height: height * zoom,
					overflow: 'hidden',
					zIndex: 2,
				}}
			>
				<canvas ref={verticalRef} />
			</div>
		</>
	);
};

export default Rulers;
