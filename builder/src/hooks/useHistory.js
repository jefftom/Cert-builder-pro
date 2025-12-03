/**
 * Undo/Redo history hook
 */

import { useState, useCallback } from '@wordpress/element';

const MAX_HISTORY = 50;

export const useHistory = (initialState) => {
	const [history, setHistory] = useState([]);
	const [currentIndex, setCurrentIndex] = useState(-1);

	const canUndo = currentIndex > 0;
	const canRedo = currentIndex < history.length - 1;

	const pushState = useCallback((state) => {
		if (!state) return;

		setHistory((prev) => {
			// Remove any future states if we're not at the end
			const newHistory = prev.slice(0, currentIndex + 1);

			// Add new state
			newHistory.push(JSON.parse(JSON.stringify(state)));

			// Limit history size
			if (newHistory.length > MAX_HISTORY) {
				newHistory.shift();
			}

			return newHistory;
		});

		setCurrentIndex((prev) => Math.min(prev + 1, MAX_HISTORY - 1));
	}, [currentIndex]);

	const undo = useCallback(() => {
		if (!canUndo) return null;

		setCurrentIndex((prev) => prev - 1);
		return history[currentIndex - 1];
	}, [canUndo, currentIndex, history]);

	const redo = useCallback(() => {
		if (!canRedo) return null;

		setCurrentIndex((prev) => prev + 1);
		return history[currentIndex + 1];
	}, [canRedo, currentIndex, history]);

	const clear = useCallback(() => {
		setHistory([]);
		setCurrentIndex(-1);
	}, []);

	return {
		canUndo,
		canRedo,
		undo,
		redo,
		pushState,
		clear,
		currentState: history[currentIndex] || null,
	};
};

export default useHistory;
