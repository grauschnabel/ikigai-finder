const { registerBlockType } = wp.blocks;
const { __ }                = wp.i18n;

registerBlockType(
	'ikigai-finder/chat-block',
	{
		title: __( 'Ikigai Chat', 'ikigai-finder' ),
		icon: 'format-chat',
		category: 'widgets',
		attributes: {},

		edit: function () {
			return (
			<div className="wp-ikigai-chat-preview">
				<h3>Ikigai Chat</h3>
				<p>Dieser Block zeigt einen interaktiven Chat zur Ikigai-Findung an.</p>
			</div>
			);
		},

		save: function () {
			return null;
		}
	}
);
