<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class PSource_Support_Tickets_Table extends WP_List_Table {

    function __construct( $args = array() ){

        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Ticket', 'psource-support' ),  
            'plural'    => __( 'Tickets', 'psource-support' ), 
            'ajax'      => false        
        ) );

        $defaults = array_merge( $this->_args, array( 'status' => 'all' ) );
        $this->_args = wp_parse_args( $args, $defaults );
        
    }


    function column_default( $item, $column_name ) {

        $value = '';
        switch ( $column_name ) {
            case 'id'           : $value = (int)$item->ticket_id; break;
            case 'staff'        : $value = $item->get_staff_name(); break;              
        }
        return $value;
    }

    function column_priority( $item ) {
        $priority_name = psource_support_get_ticket_priority_name( (int)$item->ticket_priority );
        $class = 'dashicons-before dashicons-marker ticket-priority-' . $item->ticket_priority;
        return '<span class="' . $class . '"> ' . $priority_name . '</span>';
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->ticket_id                
        );
    }

    function column_status( $item ) {
        $status_name = psource_support_get_ticket_status_name( (int)$item->ticket_status );
        $class = 'dashicons-before ';

        switch ( $item->ticket_status ) {
            case 0: { $class .= 'dashicons-star-filled'; break; }
            case 1: { $class .= 'dashicons-format-status'; break; }
            case 2: { $class .= 'dashicons-id'; break; }
            case 3: { $class .= 'dashicons-businessman'; break; }
            case 4: { $class .= 'dashicons-backup'; break; }
            case 5: { $class .= 'dashicons-no'; break; }
        } 

        $plugin = psource_support();
        $plugin::$ticket_status = array(
                0   =>  __( 'Neu', 'psource-support' ),
                1   =>  __( 'In Bearbeitung', 'psource-support' ),
                2   =>  __( 'Warten auf die Antwort des Benutzers', 'psource-support' ),
                3   =>  __( 'Warten auf Antwort des Admin', 'psource-support' ),
                4   =>  __( 'Stockt', 'psource-support' ),
                5   =>  __( 'Geschlossen', 'psource-support' )
            );


        return '<span class="' . $class . '"> ' . $status_name . '</span>';
    }

    function column_category( $item ) {
        return $item->get_category_name();
    }

    function column_subject( $item ) {

        // Link to the single ticket page
        $link = add_query_arg(
            array( 
                'tid' => (int)$item->ticket_id,
                'action' => 'edit'
            ),
            apply_filters( 'support_system_tickets_table_menu_url', '' )
        );

        $delete_link = add_query_arg( 
            array( 
                'action' => 'delete', 
                'tid' => (int)$item->ticket_id 
            )
        );
        $open_link = add_query_arg( 
            array( 
                'action' => 'open', 
                'tid' => (int)$item->ticket_id 
            ) 
        );
        $close_link = add_query_arg( 
            array( 
                'action' => 'close', 
                'tid' => (int)$item->ticket_id 
            )
        );

        $actions = array(
            'edit'    => sprintf( __( '<a href="%s">Bearbeiten</a>', 'psource-support' ), $link ),
            'delete'    => sprintf( __( '<a href="%s">Ticket löschen</a>', 'psource-support' ), $delete_link )
        );

        if ( psource_support_current_user_can( 'open_ticket', $item->ticket_id ) )
            $actions['open'] = sprintf( __( '<a href="%s" class="open-ticket">Öffne Ticket</a>', 'psource-support' ), $open_link );

        if ( psource_support_current_user_can( 'close_ticket', $item->ticket_id ) )
            $actions['close'] = sprintf( __( '<a href="%s" class="close-ticket">Ticket schließen</a>', 'psource-support' ), $close_link );

        $status = $this->_args['status'];

        if ( 'archive' == $status ) {
            unset( $actions['close'] );       
        }
        else {
            if ( 5 == (int)$item->ticket_status ) {
                unset( $actions['close'] );    
            }
            else {
                unset( $actions['open'] );    
                unset( $actions['delete'] );    
            }

        }

        if ( ! psource_support_current_user_can( 'delete_ticket' ) && isset( $actions['delete'] ) )
            unset( $actions['delete'] );

        $actions = apply_filters( 'support_system_tickets_actions', $actions, $item );        

        return '<a href="' . $link . '">' . stripslashes_deep( $item->title ) . '</a>' . $this->row_actions($actions); 
        
    }

    function column_submitted( $item ) {

        $value = __( 'Unknown', 'psource-support' );

        if ( is_multisite() ) {
            $blog_details = get_blog_details( array( 'blog_id' => (int)$item->blog_id ) );
            
            if ( ! empty( $blog_details ) )
                $value = '<a href="' . get_site_url( $item->blog_id ) . '">' . $blog_details->blogname . '</a>';
        }
        else {
            $user = get_userdata( $item->user_id );
            if ( ! empty( $user ) )
                $value = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->display_name . '</a>';
        }

        return $value;
    }

    function column_updated( $item ) {
        return psource_support_get_translated_date( $item->ticket_updated ); 
    }

    function column_replies( $item ) {
        return $item->num_replies;
    }


    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'id'        => __( 'Ticket ID', 'psource-support' ),
            'subject'   => __( 'Betreff', 'psource-support' ),
            'status'    => __( 'Status', 'psource-support' ),
            'priority'  => __( 'Priorität', 'psource-support' ),
            'category'  => __( 'Kategorie', 'psource-support' ),
            'staff'     => __( 'Mitarbeiter', 'psource-support' ),
            'submitted' => __( 'Eingereicht von', 'psource-support' ),
            'replies' => __( 'Antwort Nr.', 'psource-support' ),
            'updated'   => __( 'Letzte Aktualisierung (GMT)', 'psource-support' )
        );

        if ( ! $this->get_bulk_actions() )
            unset( $columns['cb'] );
        
        return apply_filters( 'support_network_ticket_columns', $columns );
    }

    protected function get_sortable_columns() {
        return array(
            'subject'       => array( 'title', false ),
            'status'        => array( 'ticket_status', false ),
            'priority'      => array( 'ticket_priority', false ),
            'staff'         => array( 'admin_id', false ),
            'category'      => array( 'cat_id', false ),
            'replies'       => array( 'num_replies', false ),
            'submitted'       => array( 'blog_id', false ),
            'updated'       => array( 'ticket_updated', true )
        );
    }

    function extra_tablenav( $which ) {
        if ( 'top' == $which) {

            $cat_filter_args = array(
                'show_empty' => __( 'Alle Kategorien anzeigen', 'psource-support' ),
                'selected' => isset( $_GET['category'] ) ? absint( $_GET['category'] ) : false
            );

            $priority_filter_args = array(
                'show_empty' => __( 'Alle Prioritäten', 'psource-support' ),
                'selected' => isset( $_GET['priority'] ) ? absint( $_GET['priority'] ) : null
            );

            ?>
                <div class="alignleft actions">
                    <?php psource_support_ticket_categories_dropdown( $cat_filter_args ); ?>
                    <?php psource_support_priority_dropdown( $priority_filter_args ); ?>
                    <input type="submit" name="filter_action" id="ticket-query-submit" class="button" value="<?php echo esc_attr( 'Filter', 'psource-support' ); ?>">     
                </div>
        <?php
           
                
        }
        
    }

    function get_bulk_actions() {
        $actions = array();

        if ( psource_support_current_user_can( 'delete_ticket' ) )
            $actions['delete'] = __( 'Löschen', 'psource-support' );

        if ( psource_support_current_user_can( 'open_ticket' ) )
            $actions['open'] = __( 'Öffnen', 'psource-support' );

        if ( psource_support_current_user_can( 'close_ticket' ) )
            $actions['close'] = __( 'Schließen', 'psource-support' );

        if ( 'archive' == $this->_args['status'] ) {
            unset( $actions['close'] );
        }
        elseif ( 'active' == $this->_args['status'] ) {
            unset( $actions['delete'] );
            unset( $actions['open'] );
        }

        $actions = apply_filters( 'support_system_tickets_bulk_actions', $actions );

        return $actions;

        
        
    }

    function process_bulk_action() {
        
        if( 'delete' === $this->current_action() && psource_support_current_user_can( 'delete_ticket' ) ) {

            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) ) {
                foreach ( $_POST['ticket'] as $ticket_id ) {
                    if ( psource_support_is_ticket_closed( $ticket_id ) ) {
                        psource_support_delete_ticket( absint( $ticket_id ) );
                    }
                }
            }
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) ) {
                $ticket = psource_support_get_ticket( $_GET['tid'] );
                if ( $ticket )
                    psource_support_delete_ticket( $ticket->ticket_id );
            }

        }

        if( 'open' === $this->current_action() ) {
            $ids = array();
            
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) )
                $ids = $_POST['ticket'];
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) )
                $ids = array( $_GET['tid'] );

            $ids = array_map( 'absint', $ids );
            foreach ( $ids as $id ) {
                if ( psource_support_current_user_can( 'open_ticket', $id ) )
                    psource_support_restore_ticket_previous_status( $id );
            }
        }

        if( 'close' === $this->current_action() ) {
            $ids = array();
            if ( isset( $_POST['ticket'] ) && is_array( $_POST['ticket'] ) )
                $ids = $_POST['ticket'];
            elseif ( isset( $_GET['tid'] ) && is_numeric( $_GET['tid'] ) )
                $ids = array( $_GET['tid'] );

            $ids = array_map( 'absint', $ids );
            foreach ( $ids as $id ) {
                if ( psource_support_current_user_can( 'close_ticket', $id ) )
                    psource_support_close_ticket( $id );
            }
        }

    }

    function single_row( $item ) {
        static $row_class = '';

        $row_class = ( $row_class == '' ? ' class="alternate"' : '' );

        $background = '';
        if ( ! $item->view_by_superadmin && psource_support_current_user_can( 'manage_options' ) )
            $background .= 'style="background-color:#e8f3b9" ';

        echo '<tr ' . $background . $row_class . '>';
        echo $this->single_row_columns( $item );
        echo '</tr>';
    }


    function prepare_items() {

        $current_screen = get_current_screen();
        $screen_option = $current_screen->get_option( 'per_page', 'option' );

        $per_page = get_user_meta( get_current_user_id(), $screen_option, true );
        if ( empty ( $per_page ) || $per_page < 1 ) {
            $per_page = $current_screen->get_option( 'per_page', 'default' );
        }

        $columns = $this->get_columns();
        $hidden = array( 'id' );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns, 
            $hidden, 
            $sortable
        );

        $this->process_bulk_action();
        $current_page = $this->get_pagenum();        

        $args = array(
            'status' => $this->_args['status'],
            'per_page' => $per_page,
            'page' => $current_page
        );

        $orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : false;
        $order = isset( $_GET['order'] ) ? $_GET['order'] : false;

        if ( $orderby )
            $args['orderby'] = $orderby;

        if ( $order )
            $args['order'] = $order;

        /**
         * Filters the query arguments in Tickets table
         *
         * @param Array $args Query arguments that will  be passed to psource_support_get_tickets function
         */
        $args = apply_filters( 'support_system_tickets_table_query_args', $args );

        $this->items = psource_support_get_tickets( $args );
        $total_items = psource_support_get_tickets_count( $args );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                
            'per_page'    => $per_page,                   
            'total_pages' => ceil($total_items/$per_page) 
        ) );

    }

}
?>