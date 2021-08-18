<?php
    /**
     * @author Denis Genitoni <denis.genitoni@m4ss.net>
     * @copyright 2021 M4SS
     * @description This php script help you to move all email received in your INBOX in specific folders, depending by project
     */
    
    class MoveEmail{
        private $projects_email = array(); // array for save hosts and projects

        private $server;

        private $credentials = array();

        private $imap; // general imap

        private $inbox; //inbox email

        private $dirs = array(); // all mailboxes dirs

        private $email = array(); // list of all email;

        private $folders;

        private $numEmail;

        public function __construct(string $json)
        {
            $result = $this->saveCredentials($json);
            if(!$result)
                die("Impossible to find credentials.json file");
        }
        // connect with server
        public function connection()
        {
            $this->imap = imap_open($this->server, $this->credentials[0], $this->credentials[1]); // connect to imap server
        }
        
        // move email function
        public function moveEmail()
        {
            $email = $this->getEmail(); // get email array 
            $num_email = count($this->getEmail()); // number of email in array
            $num_projects = count($this->projects_email); // number of projects

            for($i = 0; $i < $num_email; $i++)
            {
                $host = $email[$i]['host']; // obtain host value
                $udate = $email[$i]['udate']; // obtain udate value
                $number = $email[$i]['number']; // obtain number value
                $subject = $email[$i]['subject']; // obtain subject
                for($o = 0; $o < $num_projects; $o++)
                {
                    $project = $this->projects_email[$o]['project'];
                    $p_email = $this->projects_email[$o]['email'];

                    if($host == $p_email)
                    {
                        echo "La mail con host " . $host . " appartiene al progetto " . $project . " con l'oggetto " . $subject . " con numero " . $number;
                        imap_mail_move($this->imap, $number, $this->getInbox($project));
                    }
                }
            }


        }
        // obtain a list of directories of inbox
        public function listDirs()
        {
            $this->dirs = imap_list($this->imap, $this->server, '*');
        }

        // save folders in a tmp array. Is for make some operations
        protected function saveFolders(array $folders)
        {
            $this->folders = $folders;
        }

        // obtain a list of email and save in a array
        public function listEmail()
        {
            // searh email on INBOX
            $inbox = $this->getListDirs()[0];

            for($i = 1; $i <= $this->getNumEmail(); $i++)
            {
                $header = imap_headerinfo($this->imap, $i);

                $this->email[] = array(
                        "from" => $header->from[0]->mailbox,
                        "host" => $header->from[0]->host,
                        'udate' => $header->udate,
                        'number' => $i,
                        'subject' => $header->subject
                );
            }

        }

        // close connection
        public function closeConnection(bool $r = true)
        {
            if($r)
                imap_expunge($this->getImap()); // delete all email marked to deletion
            return imap_close($this->getImap()); // close imap connection
        }

        /**
         * @return bool
         * @method private for save all credentials from a json file
         */
        private function saveCredentials(string $jsonFile)
        {
            if(!is_file($jsonFile)) // check if credentials file is avaiable
                return false;
            
            $json = file_get_contents($jsonFile); // read credentials file
            $json = json_decode($json, true); // decode json file in an array and not in a stdClass
            // var_dump($json); // for debug

            $this->server = $json['data']['server']; // server data

            $this->projects_email = $json['data']['projects_email'];

            // var_dump($this->projects_email); // for debug

            // access credentials for imap server

            $email = $json['data']['credentials']['email']; // email address
            $password = $json['data']['credentials']['password']; // password

            array_push($this->credentials, $email, $password); // save imap credentials in an array

            return true;
        }

        public function getListDirs()
        {
            return $this->dirs;
        }

        // method for debug
        public function debugListDirs()
        {
            var_dump($this->getListDirs());
        }

        // method for debug
        public function printListDirs()
        {
            echo "<ul>";

            $folders = array();

            foreach($this->dirs as $dir)
            {
                $dir = str_replace($this->server, '', imap_utf7_decode($dir));
                echo "<li>" . $dir . "</li>";
                array_push($folders, $dir);
            }

            $this->saveFolders($folders);

            echo "</ul>";
        }

        // getters - public methods

        /**
         * @return array|string 
         */

        
        public function getNumEmail()
        {
            return $this->numEmail = imap_num_msg($this->imap);
        }

        public function getInbox(string $param = "")
        {
            if($param == "")
                return $this->getListDirs()[0];
            else{
                return 'INBOX.' . $param;
            }
                
        }



        public function getEmail()
        {
            return $this->email;
        }

        public function getCredentials()
        {
            return $this->credentials;
        }

        public function getImap()
        {
            return $this->imap;
        }




    }

    $email = new MoveEmail('credentials.json'); // specify path/to/filename.json
    $email->connection(); // connect to imap server
    $email->listDirs(); // obtain directories list of inbox
    // $email->printListDirs(); // for debug
    $email->listEmail(); // obtain list of email
    $email->moveEmail(); // move email to a specific directory, by host of email
    $email->closeConnection(); // close connection and remove not necessary email (use false parameters for keep these email)

    var_dump($email->getEmail()); // debug
