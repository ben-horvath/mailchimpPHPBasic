<?php

class MailChimp {

    private $url_base;
    private $data_center;
    private $api_key;

    private $lists;

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
        $this->data_center = substr( strrchr( $api_key, '-' ), 1 );
        $this->url_base = 'http://' . $this->data_center . '.api.mailchimp.com/3.0';
    }

    public function subscribe( $list_id, $email, $interest_group = null, $name = null, $fields = null ) {
        $request_type = 'POST';
        $subscribe_res = $this->url_base . '/lists/' . $list_id . '/members';
        $merge_fields = new stdClass();
        $payload = array(
            'email_address' => $email,
            'status' => 'subscribed'
        );
        if( !empty( $name ) ) {
            $merge_fields->FNAME = $name;
            $merge_fields->LNAME = "";
            $payload['merge_fields'] = $merge_fields;
        }
        if( gettype( $fields ) == "array" ) {
            $merges_available = $this->get_merge_fields( $list_id );
            if( gettype( $merges_available ) == 'array' ) {
                foreach( $fields as $key => $value ) {
                    if( in_array( $key, $merges_available ) ) {
                        $merge_fields->{$key} = $value; /* TODO: check if the actual key can be a php object property */
                    } else {
                        /* TODO: handle error */
                    }
                }
            } else {
                /* TODO: handle error */
            }
        }

        /* Set interest group(s) */
        if( is_array( $interest_group ) ) {
            if( count( $interest_group ) ) {
                $interests = array();
                for( $i = 0; $i < count( $interest_group ); $i++ ) {
                    if( strlen( $interest_group[$i] ) ) {
                        $interests[$interest_group[$i]] = true;
                    }
                }
                $payload['interests'] = (object) $interests;
            }
        } else if( is_string( $interest_group ) ) {
            if( strlen( $interest_group ) ) {
                $payload['interests'] = (object) array( $interest_group => true );
            }
        }

        $payload = json_encode( $payload );

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $subscribe_res );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );

        curl_close( $ch );

        return $response;
    }

    public function unsubscribe( $list_id, $email ) {
        $request_type = 'DELETE';
        $subscriber_hash = md5( $email );
        $unsubscribe_res = $this->url_base . '/lists/' . $list_id . '/members/' . $subscriber_hash;

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $unsubscribe_res );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );

        curl_close( $ch );

        return $response;
    }
    
    public function subscriber_info( $list_id, $email ) {
        $request_type = 'GET';
        $subscriber_hash = md5( $email );
        $subscriber_info_res = $this->url_base . '/lists/' . $list_id . '/members/' . $subscriber_hash;

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $subscriber_info_res );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );

        curl_close( $ch );

        return $response;
    }

    /*
        Returns an array of interest groups (interest categories).
        An interest group object conains an id, a title and an array of interests.
        An interest object contains an id and a name.
    */
    public function interest_grouping_info( $list_id ) {
        $request_type = 'GET';
        $interest_group_res = $this->url_base . '/lists/' . $list_id . '/interest-categories?fields=categories.id,categories.title';

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $interest_group_res );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $categories_json = curl_exec( $ch );
        /* TODO: error handling */

        $categories = json_decode($categories_json);

        $groupings = array();

        foreach( $categories->categories as $category ) {
            $interests_resource = $this->url_base . '/lists/' . $list_id . '/interest-categories/' . $category->id . '/interests?fields=interests.id,interests.name';
            
            curl_setopt( $ch, CURLOPT_URL, $interests_resource );

            $interests_json = curl_exec( $ch );
            /* TODO: error handling */

            $interests = json_decode($interests_json);

            $category->interests = array();

            foreach( $interests->interests as $interest ) {
                $category->interests[] = $interest;
            }

            $groupings[] = $category;
        }

        curl_close( $ch );

        return $groupings;
    }

    public function get_merge_fields( $list_id ) {
        try {
            $merge_fields = array();

            if( gettype( $list_id ) != "string" || strlen( $list_id ) < 1 ) {
                throw new Exception( 'Argument error in get_merge_fields().', 1 );
            }

            $lists = $this->get_lists();
            if( gettype( $lists ) != 'array' ) {
                throw $lists;
            }

            if( !in_array( $list_id, $lists ) ) {
                throw new Exception( 'No such list in this MailChimp account.', 1 );
            }

            $request_type = 'GET';
            $merge_fields_res = $this->url_base . '/lists/' . $list_id . '/merge-fields';

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
            curl_setopt( $ch, CURLOPT_URL, $merge_fields_res );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

            $response_json = curl_exec( $ch );

            $response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            if( $response_code != 200 ) {
                throw new Exception( 'MailChimp could not return the merge fields.', 2 );
            }

            $response = json_decode( $response_json );
            if( !isset( $response->merge_fields ) || gettype( $response->merge_fields ) != 'array' ) {
                throw new Exception( 'MailChimp returned with an error.', 2 );
            }

            foreach( $response->merge_fields as $merge_field ) {
                $merge_fields[] = $merge_field->tag;
            }

            return $merge_fields;
        } catch( Exception $e ) {
            return $e;
        }
    }

    public function get_lists( $flush = false ) {
        try {
            if( gettype( $flush ) != 'boolean' ) {
                throw new Exception( 'Argument error in get_lists().', 1 );
            }

            if( gettype( $this->lists ) == 'array' && !$flush ) {
                return $this->lists;
            }

            $lists = array();

            $request_type = 'GET';
            $merge_fields_res = $this->url_base . '/lists';

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
            curl_setopt( $ch, CURLOPT_URL, $merge_fields_res );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

            $response_json = curl_exec( $ch );

            $response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            if( $response_code != 200 ) {
                throw new Exception( 'MailChimp could not return the lists.', 2 );
            }
                
            $response = json_decode( $response_json );
            if( !isset( $response->lists ) || gettype( $response->lists ) != 'array' ) {
                throw new Exception( 'MailChimp returned with an error.', 2 );
            }

            foreach( $response->lists as $list ) {
                $lists[] = $list->id;
            }

            $this->lists = $lists;

            return $lists;
        } catch( Exception $e ) {
            return $e;
        }
    }
}

?>
