/**
 * Toolbar Component
 */

import { __ } from '@wordpress/i18n';
import { TextControl, Button } from '@wordpress/components';

const Toolbar = ({
	template,
	onSave,
	saving,
	canUndo,
	canRedo,
	onUndo,
	onRedo,
	onTitleChange,
}) => {
	return (
		<div className="cb-toolbar">
			<div className="cb-toolbar-left">
				<a href="admin.php?page=certbuilder-templates" className="cb-back-btn">
					← {__('Back to Templates', 'certbuilder-pro')}
				</a>

				<div className="cb-toolbar-divider" />

				<input
					type="text"
					className="cb-template-title-input"
					value={template?.title || ''}
					onChange={(e) => onTitleChange(e.target.value)}
					placeholder={__('Template Name', 'certbuilder-pro')}
				/>
			</div>

			<div className="cb-toolbar-center">
				<button
					className="cb-toolbar-btn"
					onClick={onUndo}
					disabled={!canUndo}
					title={__('Undo (Ctrl+Z)', 'certbuilder-pro')}
				>
					↩
				</button>
				<button
					className="cb-toolbar-btn"
					onClick={onRedo}
					disabled={!canRedo}
					title={__('Redo (Ctrl+Shift+Z)', 'certbuilder-pro')}
				>
					↪
				</button>
			</div>

			<div className="cb-toolbar-right">
				<button
					className="cb-toolbar-btn cb-preview-btn"
					onClick={() => {
						// TODO: Implement preview
						alert(__('Preview coming soon!', 'certbuilder-pro'));
					}}
				>
					{__('Preview', 'certbuilder-pro')}
				</button>

				<Button
					variant="primary"
					onClick={onSave}
					disabled={saving}
					className="cb-save-btn"
				>
					{saving ? __('Saving...', 'certbuilder-pro') : __('Save Template', 'certbuilder-pro')}
				</Button>
			</div>
		</div>
	);
};

export default Toolbar;
