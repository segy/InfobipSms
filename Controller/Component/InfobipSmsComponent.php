<?php
App::uses('Component', 'Controller');

/**
 * Component for sending sms messages using Infobip service
 *
 * @author segy
 * @package Sms
 */
class InfobipSmsComponent extends Component {
    /**
     * Send SMS API URL
     */
    const INFOBIP_SEND_URL = 'http://api.infobip.com/api/v3/sendsms/json';

    /**
     * Get number of credits url
     */
    const INFOBIP_CREDITS_URL = 'http://api2.infobip.com/api/command?username=%s&password=%s&cmd=credits';

    /**
     * Set username and password
     *
     * @param string
     * @param string
     * @return $this for method chaining
     */
    public function setCredentials($username, $password) {
        $this->settings['username'] = $username;
        $this->settings['password'] = $password;
        return $this;
    }

    /**
     * Set sender
     *
     * @param string
     * @return $this for method chaining
     */
    public function setSender($sender) {
        $this->settings['sender'] = $sender;
        return $this;
    }

    /**
     * Set default prefix
     *
     * @param string
     * @return $this for method chaining
     */
    public function setDefaultPrefix($prefix) {
        $this->settings['default_prefix'] = $prefix;
        return $this;
    }

    /**
     * Send message
     *
     * @param string
     * @param mixed number (numbers)
     * @return array
     */
    public function send($message, $numbers) {
        // process numbers
        if (is_array($numbers))
            $numbers = array_values($numbers);
        else
            $numbers = array($numbers);

        foreach ($numbers as $k => $number)
            $numbers[$k] = array('gsm' => $this->_parseNumber($number));

        // process text
        $message = iconv('UTF-8', 'ASCII//TRANSLIT', $message);
        $message = substr($message, 0, 160);

        $struct = array(
            'authentication' => array(
                'username' => $this->settings['username'],
                'password' => $this->settings['password']
            ),
            'messages' => array(
                array(
                    'sender' => $this->settings['sender'],
                    'text' => $message,
                    'recipients' => $numbers
                )
            )
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::INFOBIP_SEND_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'JSON=' . json_encode($struct));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);

        return $response['results'];
    }

    /**
     * Get number of credits
     *
     * @return int
     */
    public function getCredits() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf(self::INFOBIP_CREDITS_URL, $this->settings['username'], $this->settings['password']));
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        return (int)$response;
    }

    /**
     * Parse number to required format
     *
     * @param string
     * @return string
     */
    protected function _parseNumber($number) {
        $number = trim($number);

        // decide whether already has prefix
        if (!preg_match('/^(00|\+)/', $number)) {
            if (substr($number, 0, 1) === '0')
                $number = substr($number, 1);

            $number = $this->settings['default_prefix'] . $number;
        }
        else
            $number = preg_replace('/^(00|\+)/', '', $number);

        return preg_replace('/[^0-9]/', '', $number);
    }
}