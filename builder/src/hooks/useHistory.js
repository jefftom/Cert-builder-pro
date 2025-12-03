/**
 * Undo/Redo history hook with proper state management
 * Uses snapshot-based history with debouncing
 */

import { useState, useCallback, useRef, useEffect } from '@wordpress/element';

const MAX_HISTORY = 50;
const DEBOUNCE_MS = 300;

export const useHistory = (template, setTemplate) => {
	// History stack: array of template snapshots
	const [history, setHistory] = useState([]);
	const [currentIndex, setCurrentIndex] = useState(-1);
	const [isUndoRedo, setIsUndoRedo] = useState(false);

	// Debounce timer ref
	const debounceRef = useRef(null);
	// Track if we've initialized with the first state
	const initializedRef = useRef(false);

	// Initialize history with first state
	useEffect(() => {
		if (template && !initializedRef.current) {
			const snapshot = JSON.parse(JSON.stringify(template));
			setHistory([snapshot]);
			setCurrentIndex(0);
			initializedRef.current = true;
		}
	}, [template]);

	// Track template changes and push to history (debounced)
	useEffect(() => {
		// Skip if this change was caused by undo/redo
		if (isUndoRedo) {
			setIsUndoRedo(false);
			return;
		}

		// Skip if not initialized or no template
		if (!initializedRef.current || !template) return;

		// Clear existing debounce timer
		if (debounceRef.current) {
			clearTimeout(debounceRef.current);
		}

		// Debounce the history push
		debounceRef.current = setTimeout(() => {
			const snapshot = JSON.parse(JSON.stringify(template));
			const currentSnapshot = history[currentIndex];

			// Only push if state actually changed
			if (JSON.stringify(snapshot) !== JSON.stringify(currentSnapshot)) {
				setHistory((prev) => {
					// Remove any future states (we're branching from current point)
					const newHistory = prev.slice(0, currentIndex + 1);

					// Add new snapshot
					newHistory.push(snapshot);

					// Limit history size
					if (newHistory.length > MAX_HISTORY) {
						return newHistory.slice(1);
					}

					return newHistory;
				});

				setCurrentIndex((prev) => Math.min(prev + 1, MAX_HISTORY - 1));
			}
		}, DEBOUNCE_MS);

		return () => {
			if (debounceRef.current) {
				clearTimeout(debounceRef.current);
			}
		};
	}, [template, currentIndex, history, isUndoRedo]);

	const canUndo = currentIndex > 0;
	const canRedo = currentIndex < history.length - 1;

	const undo = useCallback(() => {
		if (!canUndo) return;

		const newIndex = currentIndex - 1;
		const previousState = history[newIndex];

		if (previousState) {
			setIsUndoRedo(true);
			setCurrentIndex(newIndex);
			setTemplate(JSON.parse(JSON.stringify(previousState)));
		}
	}, [canUndo, currentIndex, history, setTemplate]);

	const redo = useCallback(() => {
		if (!canRedo) return;

		const newIndex = currentIndex + 1;
		const nextState = history[newIndex];

		if (nextState) {
			setIsUndoRedo(true);
			setCurrentIndex(newIndex);
			setTemplate(JSON.parse(JSON.stringify(nextState)));
		}
	}, [canRedo, currentIndex, history, setTemplate]);

	const clear = useCallback(() => {
		setHistory([]);
		setCurrentIndex(-1);
		initializedRef.current = false;
	}, []);

	// Force push current state (useful after batch operations)
	const forcePush = useCallback(() => {
		if (!template) return;

		const snapshot = JSON.parse(JSON.stringify(template));

		setHistory((prev) => {
			const newHistory = prev.slice(0, currentIndex + 1);
			newHistory.push(snapshot);

			if (newHistory.length > MAX_HISTORY) {
				return newHistory.slice(1);
			}

			return newHistory;
		});

		setCurrentIndex((prev) => Math.min(prev + 1, MAX_HISTORY - 1));
	}, [template, currentIndex]);

	return {
		canUndo,
		canRedo,
		undo,
		redo,
		clear,
		forcePush,
		historyLength: history.length,
		currentIndex,
	};
};

export default useHistory;
