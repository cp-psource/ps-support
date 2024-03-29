<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class PSource_Support_FAQS_Table extends WP_List_Table {

    function __construct( $args = array() ){

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'FAQ', 'psource-support' ),  
            'plural'    => __( 'FAQs', 'psource-support' ), 
            'ajax'      => false        
        ) );
        
    }

    function column_default( $item, $column_name ) {
        return $item->$column_name;
    }

    function column_category( $item ) {
        return $item->category->cat_name;
    }

    function column_question( $item ) {

        // Link to the single FAQ page
        
        /**
         * Filters the FAQ Menu URL in the question column in FAQs table
         * @var [type]
         */
        $menu_url = apply_filters( 'support_system_faqs_table_menu_url', '' );
        $link = add_query_arg(
            array( 
                'fid' => (int)$item->faq_id,
                'action' => 'edit'
            ),
            $menu_url
        );

        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete', 
                'fid' => (int)$item->faq_id 
            )
        );
        
        $actions = array(
            'edit'     => sprintf( __( '<a href="%s">FAQ bearbeiten</a>', 'psource-support' ), $link ),
            'delete'    => sprintf( __( '<a href="%s">FAQ löschen</a>', 'psource-support' ), $delete_link ),
        );

        /**
         * Filters the actions under FAQ title in FAQs table
         * 
         * @param Array $actions Array of actions
         * @param Object $item Current FAQ Object
         */
        $actions = apply_filters( 'support_system_faqs_actions', $actions, $item );        

        return '<a href="' . $link . '">' . $item->question . '</a>' . $this->row_actions($actions);
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->faq_id                
        );
    }

    function column_helpful( $item ) {

        if ( $item->help_yes + $item->help_no == 0 )
            $value = 0;
        else
            $value = number_format_i18n( ( $item->help_yes / ( $item->help_yes + $item->help_no ) )  * 100, 0 );

        $class = '';
        if ( $value >= 80 )
            $class = 'psource-support-meter-high';
        elseif ( $value < 80 && $value >= 40 )
            $class = 'psource-support-meter-mid';
        else
            $class = 'psource-support-meter-low';

        ob_start();
        ?>
        <div class="psource-support-meter">
            <?php if ( ! $value ): ?>
                0 %
            <?php else: ?>
                <span class="psource-support-meter-yes" style="width: <?php echo $value; ?>%"><?php echo $value; ?> %</span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();        
    }

    function column_no_helpful( $item ) {
        if ( $item->help_yes + $item->help_no == 0 )
            $value = 0;
        else
            $value = number_format_i18n( ( $item->help_no / ( $item->help_yes + $item->help_no ) )  * 100, 0 );

        $class = '';
        if ( $value >= 80 )
            $class = 'psource-support-meter-low';
        elseif ( $value < 80 && $value >= 40 )
            $class = 'psource-support-meter-mid';
        else
            $class = 'psource-support-meter-high';

        ob_start();
        ?>
        <div class="psource-support-meter">
            <?php if ( ! $value ): ?>
                0 %
            <?php else: ?>
                <span class="psource-support-meter-no" style="width: <?php echo $value; ?>%"><?php echo $value; ?> %</span>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean();
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'faq_id'            => __( 'Frage ID', 'psource-support' ),
            'question'      => __( 'Frage', 'psource-support' ),
            'category'      => __( 'Kategorie', 'psource-support' ),
            'helpful'       => __( 'Denken ist hilfreich', 'psource-support' ),
            'no_helpful'    => __( 'Denken ist nicht hilfreich', 'psource-support' )
        );
        return $columns;
    }

    function extra_tablenav( $which ) {
        if ( 'top' == $which) {

            $cat_filter_args = array(
                'show_empty' => __( 'Alle Kategorien anzeigen', 'psource-support' ),
                'selected' => isset( $_GET['category'] ) ? absint( $_GET['category'] ) : false
            );


            ?>
                <div class="alignleft actions">
                    <?php psource_support_faq_categories_dropdown( $cat_filter_args ); ?>
                    <input type="submit" name="filter_action" id="faq-query-submit" class="button" value="<?php echo esc_attr( 'Filter', 'psource-support' ); ?>">     
                </div>
        <?php                
        }        
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => __( 'Löschen', 'psource-support' ),
        );

        if ( ! psource_support_current_user_can( 'delete_faq' ) ) {
            unset( $actions['delete'] );
        }

        /**
         * Filters the bulk actions in FAQs table
         * 
         * @param Array $actions Current Bulk Actions
         */
        $actions = apply_filters( 'support_system_faqs_bulk_actions', $actions );

        return $actions;        
    }

    function process_bulk_action() {
        if( 'delete' === $this->current_action() && psource_support_current_user_can( 'delete_faq' ) ) {

            if ( isset( $_POST['faq'] ) && is_array( $_POST['faq'] ) ) {
                foreach ( $_POST['faq'] as $faq ) {
                    psource_support_delete_faq( $faq );
                }
            }
            elseif ( isset( $_GET['fid'] ) && is_numeric( $_GET['fid'] ) ) {
                $faq = psource_support_get_faq( $_GET['fid'] );
                if ( $faq )
                psource_support_delete_faq( $faq->faq_id );
            }
        }
    }

    function prepare_items() {

        $current_screen = get_current_screen();
        $screen_option = $current_screen->get_option( 'per_page', 'option' );

        $per_page = get_user_meta( get_current_user_id(), $screen_option, true );
        if ( empty ( $per_page ) || $per_page < 1 ) {
            $per_page = $current_screen->get_option( 'per_page', 'default' );
        }

        $columns = $this->get_columns();
        $hidden = array( 'faq_id' );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns, 
            $hidden, 
            $sortable
        );

        $this->process_bulk_action();
        $current_page = $this->get_pagenum();        

        /**
         * Filters the query arguments for the FAQs table
         * 
         * @param Array $args Query Arguments that will be passed to psource_support_get_faqs function
         */
        $args = apply_filters( 'support_system_faqs_table_query_args', array(
            'per_page' => $per_page,
            'page' => $current_page
        ) );

        $this->items = psource_support_get_faqs( $args );
        $total_items = psource_support_get_faqs_count( $args );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );
    }
}
?>