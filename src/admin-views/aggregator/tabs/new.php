<?php
	$fields = array();
?>
<table class="form-table">
	<tbody>

		<?php
			$fields['origin'] = (object) array();

			$fields['origin']->options = array(
				array(
					'id' => 'csv',
					'text' => esc_html__( 'CSV File', 'the-events-calendar' ),
				),
				array(
					'id' => 'ics',
					'text' => esc_html__( 'ICS File', 'the-events-calendar' ),
				),
				array(
					'id' => 'facebook',
					'text' => esc_html__( 'Facebook', 'the-events-calendar' ),
				),
				array(
					'id' => 'meetup',
					'text' => esc_html__( 'Meetup', 'the-events-calendar' ),
				),
			);

			$fields['origin']->placeholder = esc_attr__( 'Select Origin', 'the-events-calendar' );

			$fields['origin']->help = esc_attr__( 'Specify the type of data you wish to import', 'the-events-calendar' );
		?>
		<tr>
			<th scope="row">
				<label for="tribe-ea-field-origin"><?php esc_html_e( 'Import Origin', 'the-events-calendar' ) ?></label>
			</th>
			<td>
				<input name="aggregator[origin]" type="hidden" id="tribe-ea-field-origin" class="tribe-ea-field tribe-ea-select2" placeholder="<?php echo $fields['origin']->placeholder; ?>" data-options="<?php echo esc_attr( json_encode( $fields['origin']->options ) ); ?>">
				<span class="tribe-ea-help dashicons dashicons-editor-help" data-help="<?php echo $fields['origin']->help; ?>"></span>
			</td>
		</tr>

	</tbody>
</table>