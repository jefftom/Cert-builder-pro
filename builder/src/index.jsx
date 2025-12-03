/**
 * CertBuilder Pro - Visual Certificate Builder
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.css';

// Get the root element
const rootElement = document.getElementById('certbuilder-builder-root');

if (rootElement) {
	const root = createRoot(rootElement);
	root.render(<App />);
}
