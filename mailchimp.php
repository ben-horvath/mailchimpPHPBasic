<?php

class MailChimp {

    private $url_base;
    private $data_center;

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
        $this->data_center = substr( strrchr( $api_key, '-' ), 1 );
        $this->url_base = 'http://' . $this->data_center . '.api.mailchimp.com/3.0';
    }

    public function subsribe( $list_id, $email, $interest_group = null ) {
        $request_type = 'POST';
        $subscribe_res = $this->url_base . '/lists/' . $list_id . '/members';
        $payload = array(
            'email_address' => $email,
            'status' => 'subscribed'
        );

        /* Set interest group(s) */
        if( is_array( $interest_group ) ) {
            if( count( $interest_group ) ) {
                $interests = array();
                for( $i = 0; $i < count( $interest_group ); $i++ ) {
                    $interests[$interest_group[$i]] = true;
                }
                $payload['interests'] = (object) $interests;
            }
        } else if( is_string( $interest_group ) ) {
            $payload['interests'] = (object) array( $interest_group => true );
        }

        $payload = json_encode( $payload );

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $subscribe_res );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );
        /* TODO: error handling */

        curl_close( $ch );
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
        /* TODO: error handling */

        curl_close( $ch );
    }
    
    public function subscriber_info( $list_id, $email ) {
        $request_type = 'GET';
        $subscriber_hash = md5( $email );
        $unsubscribe_res = $this->url_base . '/lists/' . $list_id . '/members/' . $subscriber_hash;

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_USERPWD, 'user:' . $this->api_key );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $request_type );
        curl_setopt( $ch, CURLOPT_URL, $unsubscribe_res );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $response = curl_exec( $ch );
        /* TODO: error handling */

        curl_close( $ch );

        return $response;
    }
}

?>