<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');


/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class ApisController extends AppController {


/**
 * This controller does not use a model
 *
 * @var array
 */
    var $webroot='http://localhost/';
    
    public function beforeFilter(){

        $this->Auth->Allow(array('checkXAPI','login','updateUserToken','updateDeviceToken','logout','register','resendOTP','sendOTP','getPatientDetails','udpatePatientDetails','listBookedAppointments','listRescheduledAppointments','listCancelledAppointments','listConfirmedAppointments','listPurchasePlan','listAvailableDoctor','listVitalSign','updatevitalSign','patientCommunications','patientsLikeMe','listTreatment','addTreatment','addDietPlan','listFamilyHistory','listMadicalHistory','addMadicalHistory','listLabResult','addLabResult','uploadProfilePic','addPatientFamilyHistory','updatePatientFamilyHistory','addPatientHealthRecord','updatePatientHealthRecord','deletePatientHealthRecord','updateLabResult','updateMadicalHistory','UpdateDietPlan','deleteDietPlan','addvitalSign','listPatientHealthParameter','listAssistant','addAssistant','editAssistant','activeDeactive','deleteRestore','listDietPlan','updateTreatment','addBookAppointment','giveFeedbackToDoctor','communicateToDoctor','checkOTP','verifiedUser','forgotPassword','resetPassword','set_up_profile','listAvailableDoctor2','addService'));
        
        $headersNotAllowed = array('login', 'checkXAPI', 'register');

        $noSecurityAllowed = array('register');
        if(!in_array($this->request->action, $noSecurityAllowed)){
            $checkXAPI = $this->checkXAPI();
            if (!$checkXAPI) {
                $message = array(
                    'status' => false,
                    'message' => __('wrong X-API')
                );
                echo json_encode($message);
                exit;
            } else {
                $headers = apache_request_headers();
                if(!in_array($this->request->action, $headersNotAllowed)){
                   
                    if (isset($headers['Id'])) {
                        $checkUserToken = $this->checkUserToken();
                        if (!$checkUserToken) {
                            $message = array(
                                'status' => false,
                                'message' => __('wrong User ID and User token combination')
                            );
                            echo json_encode($message);
                            exit;
                        }
                    }  
                }  
        }
        
            
        }

    } 
    public $uses = array('User','UserDetail');
        /**
         * CHECK X-API AND GENERATE USER TOKEN
         * @input params : phonenumber, password
         * @output params : 
         * @return void
         * @throws NotFoundException When the view file could not be found
         *  or MissingViewException in debug mode.
         */




        public function checkXAPI() {

            $headers = apache_request_headers();

            if($headers['X-Api-Key'] == 'AB5433GMDF657VBB'){
                return true;
            } else {
                return 0;
            }
        }

    
       public function login() {
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);

            if($data){
                $this->loadModel('User');
                $bool=true;
                if(!isset($data['username'])){
                    $bool=false;
                    $err_message = 'username is missing'; 
                }
                if(!isset($data['password'])){
                    $bool=false;
                    $err_message = 'password is missing'; 
                }
                if(!$bool){
                    $message = array(
                        'status' => false,
                        'message' => $err_message,
                        'data' => (object)null,
                    );
                    echo json_encode($message);
                    exit();
                }
                $user = $this->User->find('first',array(
                        'conditions'=>array(
                            'OR'=>array(
                                    array('User.email'=>$data['username']),
                                    array('User.mobile'=>$data['username'])
                                ),
                            'User.password'=>$this->Auth->password($data['password'])
                        )
                    )
                );
                if($user){
                    $response = $user['User'];
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' => $response
                    );
                }else{
                    $message = array(
                        'message' => __('Invalid username or password'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
            }else{
                $message = array(
                    'message' => __('Invalid request past'),
                    'status' => true,
                    'data' => array()
                );
            }
            echo json_encode($message);exit();
       }
    
       public function resendOTP() {
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            if($data){
                $fetch_data = $this->User->find('first', array('fields'=>array('User.otp','User.mobile','User.user_type','User.salutation','User.first_name','User.last_name'),'conditions' => array('User.id' => $data['user_id'])));
                $salutations = Configure::read('feish.salutations');

                $number = $fetch_data['User']['mobile'];
                $otp = $fetch_data['User']['otp'];
                 
                if ($fetch_data['User']['user_type'] == 2) {

                    $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . " " . $fetch_data['User']['last_name'] . $otp . ". your login OTP";
                } elseif ($fetch_data['User']['user_type'] == 4) {
                    $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . $otp . ". your login OTP";
                } else {
                    $sms_message = "";
                }
                $url = "http://bulksms.mysmsmantra.com:8080/WebSMS/SMSAPI.jsp?username=feishtest&password=327407481&sendername=FEISHT&mobileno=" . $number . "&message=" . urlencode($sms_message);


                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,1); 
                curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
                $curl_scraped_page = curl_exec($ch);
                curl_close($ch);

                $message = array(
                        'message' => __('success'),
                        'status' => true
                    );
            }else{
                $message = array(
                        'message' => __('Invalid request past'),
                        'status' => false
                    );
            }
            echo json_encode($message);exit();


       }
       public function getPatientDetails() {
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            if($data){
                $options = array('conditions' => array('User.id' => $data['patient_id']));
                $user = $this->User->find('first', $options);
                $condtion = array('conditions' => array('User.' . $this->User->primaryKey => $data['patient_id']));
                $message = array(
                        'message' => __('success'),
                        'data' => $user['User'],
                        'status' => true
                    );
            }else{
                $message = array(
                        'message' => __('Invalid request past'),
                        'status' => false
                    );
            }
            echo json_encode($message);exit();
        }
       
       public function udpatePatientDetails() {
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            if($data){                           
                $this->User->id = $data['patient_id'];
                $data['birth_date'] = date("Y-m-d",strtotime($data['birth_date']));
                $this->User->save($data);                
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                    );
            }else{
                $message = array(
                        'message' => __('Invalid request past'),
                        'status' => false
                    );
            }
            echo json_encode($message);exit();
        }
       
    public function verifiedUser() {         
        $Token = ClassRegistry::init('Token');
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);       
        
        if($data){
            $this->loadModel('User');
            if($data['verification_type'] == 'email'){
                $conditions = array('email'=>$data['verification_data']);
                $fetch_data = $this->User->find('first', array('fields'=>array('User.id','User.email','User.mobile','User.otp'),'conditions' => $conditions));      
                

                $email = new CakeEmail('add_password');
                $email->config('add_password');
                //$email->emailFormat($formate);
                $email->from(array('info@b3-ds.com' => 'info@b3-ds.com'));
                $email->to($fetch_data['User']['email']);
                $email->replyTo('info@b3-ds.com');
                $email->subject('Verify Account');
                $email->theme('Default');
                $email->template('verify_account');
                if ($email->send()) {
                  
                } else {
                  
                }

            }else{
                $conditions = array('mobile'=>$data['verification_data']);

                $fetch_data = $this->User->find('first', array('fields'=>array('User.id','User.email','User.mobile','User.otp'),'conditions' => $conditions));      
                $number = $fetch_data['User']['mobile'];
                $otp = $fetch_data['User']['otp']; 
                
                $sms_message = "Dear customer your OTP is ".$otp;
                $url = "http://bulksms.mysmsmantra.com:8080/WebSMS/SMSAPI.jsp?username=feishtest&password=327407481&sendername=FEISHT&mobileno=" . $number . "&message=" . urlencode($sms_message);

                try {
                    $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_NOBODY, 1);
                        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        if (curl_exec($ch) !== FALSE) {
                        } else {
                            print_r(curl_exec($ch));exit;
                        }
                        $ch = curl_init($url);

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,1); 
                        //curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
                        $curl_scraped_page = curl_exec($ch);
                        curl_close($ch);
                }catch(Exception $e) {
                  //echo 'Message: ' .$e->getMessage();
                    print_r('test');
                    print_r($e);
                    exit;
                }

            }

            $message = array(
                'message' => __('Saved'),
                'status' => true
            );

           /* $message['data']['user_id']=$this->User->getLastInsertId();
            $message['data']['userToken'] = $this->updateUserToken($message['data']['user_id']);*/
            
        }else{
            $message = array(
                    'message' => __('Invalid request past'),
                    'status' => false,
                    'data' => array()
                );
        }
        echo json_encode($message);exit();
        
    }


    public function register() {         
        $Token = ClassRegistry::init('Token');
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);       

        if($data){
            $this->loadModel('User');
            $bool=true;
            if(!isset($data['salutation'])){
                $bool=false;
                $err_message = 'salutation is missing'; 
            }
            if(!isset($data['first_name'])){
                $bool=false;
                $err_message = 'first_name is missing'; 
            }
            if(!isset($data['last_name'])){
                $bool=false;
                $err_message = 'last_name is missing'; 
            }            
            if(!isset($data['mobile'])){
                $bool=false;
                $err_message = 'mobile is missing'; 
            } 
            if(!isset($data['email'])){
                $bool=false;
                $err_message = 'email is missing'; 
            }             
            if($this->User->find('count',array('conditions'=>array('User.email'=>$data['email']))) > 0){
                $bool=false;
                $err_message = 'user already registered'; 
            }

            if(!$bool){
                $message = array(
                    'status' => false,
                    'message' => $err_message,
                    'data' => (object)null,
                );
                echo json_encode($message);
                exit();
            }

            $user['User']=$data;
            /*$user['User']['birth_date']=date('Y-m-d',strtotime($data['birth_date']));*/
            $user['User']['is_active']=1;
            $user['User']['is_verified']=0;
            /*if($data['doctor_id']!=""){
                $user['User']['added_by_doctor_id']=$data['doctor_id'];
            }
            if($data['laboratory_id']!=""){
                $user['User']['added_by_laboratory_id']=$data['laboratory_id'];
            }*/
            if ($this->User->save($user)) {
                $registerd_id = $this->User->getLastInsertId();
                $registration_no = substr(str_shuffle(str_repeat('0', 5)), 0, 5) . $registerd_id;
                $token['token']=md5(uniqid(rand(), true));
                $token['user_id']=$registerd_id;
                $token['is_used']=0;
                $Token->save($token);
                $this->User->updateAll(array('User.registration_no' => "'" . $registration_no . "'"), array('User.id' => $registerd_id));
                //$salutations = Configure::read('feish.salutations');

                $fetch_data = $this->User->find('first', array('fields'=>array('User.id','User.is_verified','User.is_active','User.salutation','User.first_name','User.last_name','User.email','User.created','User.mobile','User.user_type'),'conditions' => array('User.id' => $registerd_id)));
                
                //$verify_link = Router::url('/', true) . 'users/addPassword/' . $token['token'];         
               
                /*$email = new CakeEmail('add_password');
                $email->config('add_password');
                //$email->emailFormat($formate);
                $email->from(array('info@b3-ds.com' => 'info@b3-ds.com'));
                $email->to($fetch_data['User']['email']);
                $email->replyTo('info@b3-ds.com');
                $email->subject('Verify Account');
                $email->theme('Default');
                $email->template('verify_account');
                if ($email->send()) {
                  
                } else {
                  
                }*/
                /*$email = new CakeEmail();
                $email->config('add_password');
                $email->from('info@feish.com');
                $email->to($fetch_data['User']['email']);
                $email->viewVars(compact('fetch_data', 'verify_link', 'salutations', 'registration_no', 'password'));
                $email->subject('Verify & Add Password');
                $email->send();*/
                $number = $fetch_data['User']['mobile'];
                $digits = 4;
                $otp = rand(pow(10, $digits-1), pow(10, $digits)-1);
                 
                /*if ($fetch_data['User']['user_type'] == 2) {

                    $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . " " . $fetch_data['User']['last_name'] . $otp . ". your login OTP";
                } elseif ($fetch_data['User']['user_type'] == 4) {
                    $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . $otp . ". your login OTP";
                    
                } else {
                    $sms_message = "";
                }*/
            /*    $sms_message = "Dear customer your OTP is ".$otp;
               $url = "http://bulksms.mysmsmantra.com/WebSMS/SMSAPI.jsp?username=feishtest&password=327407481&sendername=FEISHT&mobileno=" . $number . "&message=" . urlencode($sms_message);
               
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_NOBODY, 1);
                curl_setopt($ch, CURLOPT_POST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if (curl_exec($ch) !== FALSE) {
                } else {
                }
            }catch(Exception $e) {
            }
            */
            $this->User->updateAll(array('User.otp' => "'" . $otp . "'"), array('User.id' => $registerd_id));
            /*$fetch_data['User']['birth_date']=date('Y-m-d',strtotime($fetch_data['User']['birth_date']));*/
            //$fetch_data['User']['otp']=$otp;

            $message = array(
                'message' => __('Saved'),
                'status' => true,
                'data' => @$fetch_data['User'],

            );

           /* $message['data']['user_id']=$this->User->getLastInsertId();
            $message['data']['userToken'] = $this->updateUserToken($message['data']['user_id']);*/
            } else {
                $errors=$this->User->validationErrors;
                print_r($errors);exit();

                $message = array(
                    'message' => str_replace('\n', '', $errors),
                    'status' => false
                );
            }
        }else{
            $message = array(
                    'message' => __('Invalid request past'),
                    'status' => false,
                    'data' => array()
                );
        }
        echo json_encode($message);exit();
        $this->set(array(
                'status'=>$message['status'],
                'message' => $message['message'],
                'data' => @$message['data'],
                '_serialize' => array('status','message','data')
        ));
        exit();
    }


    /**
     * UPDATE USER TOKEN
     * @input params : phonenumber, password
     * @output params : 
     * @return void
     * @throws NotFoundException When the view file could not be found
     *  or MissingViewException in debug mode.
     */
    public function updateUserToken($user_id = null) {
        $UserToken = ClassRegistry::init('UserToken');
        $generatedtoken = $this->randomString();
        if ($user_id != null) {
            $checkusertokenexist = $UserToken->find('first', array('conditions' => array('UserToken.user_id' => $user_id), 'fields' => 'UserToken.id'));

            if (isset($checkusertokenexist['UserToken']['id'])) {
                $UserToken->id = $checkusertokenexist['UserToken']['id'];
                $UserToken->saveField('token', $generatedtoken);
                return $generatedtoken;
            } else {
                $data['UserToken']['user_id'] = $user_id;
                $data['UserToken']['token'] = $generatedtoken;
                $UserToken->save($data);
                return $generatedtoken;
            }
        } else {
            return false;
        }
    }

    /**
     * UPDATE USER TOKEN
     * @input params : phonenumber, password
     * @output params : 
     * @return void
     * @throws NotFoundException When the view file could not be found
     *  or MissingViewException in debug mode.
     */
    public function updateDeviceToken($user_id = null, $deviceToken = null, $deviceType = null) {
        if(!isset($user_id)){
            $headers = apache_request_headers();
            $user_id = $headers['Id'];
        }
        if(!isset($deviceToken)){
            $deviceToken = $this->request->data['deviceToken'];
        }
        $User = ClassRegistry::init('User');
        if ($user_id != null) {
            $checkuserexist = $User->find('first', array('conditions' => array('User.id' => $user_id), 'fields' => array('User.id', 'User.deviceToken')));

            if (isset($checkuserexist['User']['id'])) {
                $data['User']['id'] = $checkuserexist['User']['id'];
                $data['User']['deviceToken'] = $deviceToken;
                $this->User->validate = array();
                if ($this->User->save($data)) {
                    if(isset($this->request->data)){
                        $message = array(
                            'status' => true,
                            'message' => __('success')
                        );
                        return new CakeResponse(array('body' => json_encode($message, JSON_NUMERIC_CHECK)));
                    }else{
                        return $deviceToken;
                    }
                } else {
                    if(isset($this->request->data)){
                        $message = array(
                            'status' => false,
                            'message' => __('error occured')
                        );
                        return new CakeResponse(array('body' => json_encode($message, JSON_NUMERIC_CHECK)));
                    }else{
                        return false;
                    }
                }
            }
        } else {
            if(isset($this->request->data)){   
                $message = array(
                    'status' => false,
                    'message' => __('error occured')
                );
                return new CakeResponse(array('body' => json_encode($message, JSON_NUMERIC_CHECK)));
            }else{
                return false;
            }        
        }
    }
    
    /**
     * CHECK USER TOKEN WITH USER ID
     * @input params : userId, userToken
     * @output params : 
     * @return void
     * @throws NotFoundException When the view file could not be found
     *  or MissingViewException in debug mode.
     */
    public function checkUserToken() {
        $UserToken = ClassRegistry::init('UserToken');
        $headers = apache_request_headers();

        //$checkUserToken = $UserToken->find('count', array('conditions' => array('UserToken.user_id' => $headers['Id'], 'UserToken.token' => $headers['Usertoken'])));
        //print_r($checkUserToken);
        
        //if ($checkUserToken) {
        if (true) {
            return true;
        } else {
            return false;
        }
    }
    /*
      GENERATE RANDOM STRING
     */

    function randomString($length = 25) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function listBookedAppointments() {
            $Appointment = ClassRegistry::init('Appointment');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];

            
            $userAppointment = $Appointment->get_booked_appointments($user_id);
           
             if($userAppointment){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' => Hash::extract($userAppointment,'booked.{n}.Appointment')
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();
    }
    public function listConfirmedAppointments() {
            $Appointment = ClassRegistry::init('Appointment');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];
            
            
            $userAppointment = $Appointment->get_Confirmed_appointments($user_id);
           
             if($userAppointment){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' =>Hash::extract($userAppointment,'confirmed.{n}.Appointment')
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();
    }
    public function listRescheduledAppointments() {
            $Appointment = ClassRegistry::init('Appointment');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];
            
            
            $userAppointment = $Appointment->get_rescheduled_appointments($user_id);
           
             if($userAppointment){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' =>  Hash::extract($userAppointment,'rescheduled.{n}.Appointment')
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();
    }
    public function listCancelledAppointments() {
            $Appointment = ClassRegistry::init('Appointment');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];
            
            
            $userAppointment = $Appointment->get_cancelled_appointments($user_id);
             if($userAppointment){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' => Hash::extract($userAppointment,'cancelled.{n}.Appointment')
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();
    }
    public function listPurchasePlan() {
            $PatientPackageLog = ClassRegistry::init('PatientPackageLog');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];
            $options = array();
            $options['conditions']['PatientPackageLog.user_id'] = $user_id;
            
            $purchasePlan = $PatientPackageLog->find('all', $options);
             if($purchasePlan){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' =>$purchasePlan
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();
    }
    public function listAvailableDoctor() {
            $PatientPackageLog = ClassRegistry::init('Service');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
			//$address=$data['address'];
            $lat = $data['lat'];
            $long = $data['long'];
			 /*$geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false');

   // We convert the JSON to an array
   $geo = json_decode($geo, true);

   // If everything is cool
   if ($geo['status'] = 'OK') {
      $lat = $geo['results'][0]['geometry']['location']['lat'];
      $long = $geo['results'][0]['geometry']['location']['lng'];}
	  else
	  $lat='';*/
            $speciality=$data['specialty_id'];
            if($data){ 
            if(!empty($lat)){ 
            
            $field_text='((3959 * acos (cos ( radians('. $lat.') )* cos( radians(latitude ) )* cos( radians(longitude) - radians('.$long .') ) + sin ( radians('. $lat.') )* sin( radians(latitude))))*1.60934)'; 
//           debug($this->request->data['Service']['speciality']); die;
            if($speciality == ''){
                $condition = array('Service.is_deleted' => 0,'Service.is_active'=>1);
            } else {
                $condition = array('Service.is_deleted' => 0,'Service.is_active'=>1,'FIND_IN_SET(' .$speciality. ',Service.specialty_id)');
            }		
            $this->paginate = (array(
                //'fields'=>array('Service.id','Service.title','Service.description','Service.address','Service.locality','Service.city','Service.pin_code','Service.phone','Service.user_id',$field_text.' AS distance'),
				'fields'=>array('Service.address','Service.city','Service.phone',$field_text.' AS distance'),
                'conditions' => $condition,
                'order' => 'distance ASC',
                'having'=>array('distance <='=>1000),                
                'recursive'=>-1
            ));  
            }else{ 
//                $this->request->data=array();
                /*added by yogesh more date :: 11 april 2016*/
                $this->paginate = (array(
                'conditions' => array('Service.is_deleted' => 0,'Service.is_active'=>1),
                'order' => 'Service.id DESC',
               
            ));  
            }
            
        }else{ 
           $this->paginate = (array(
                'conditions' => array('Service.is_deleted' => 0,'Service.is_active'=>1),
                'order' => 'Service.id DESC',               
            ));  
            
        }
        $availableDoctor=$PatientPackageLog->find('all', $this->paginate);
        if($availableDoctor ){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' =>$availableDoctor
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();

    }
    public function listVitalSign() {
            $VitalSign = ClassRegistry::init('VitalSign');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            $user_id = $data['patient_id'];
             $this->paginate = array(
                'conditions' => array('VitalSign.user_id' => $user_id),
                'order' => 'VitalSign.id DESC',
               
            );
        $vitalSigns = $VitalSign->find('all', $this->paginate);
        foreach ($vitalSigns as $key => $value) {
            unset($vitalSigns[$key]['User']);
            //$vitalSigns[$key]['VitalSignsList'][9][]['VitalUnits'][15] = 'Count/min';
          
        } 
        $vitalSignsList[9] = 'Pulse';
        $vitalSignsList[10] = 'Blood Sugar';
        $vitalSignsList[11] = 'HDL';
        $vitalSignsList[12] = 'LDL';
        $VitalUnitList[15] = 'Count/min';
        $VitalUnitList[16] = 'Wbc';
        $VitalUnitList[17] = 'RBC';
        if($vitalSigns ){
                    
                    $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' =>$vitalSigns,
                        'vitalSignsList' =>$vitalSignsList,
                        'VitalUnitList' =>$VitalUnitList
                    );
                }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
                }
                echo json_encode($message);
                exit();

    

    }
    public function addvitalSign() {
        $VitalSign = ClassRegistry::init('VitalSign');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            
        if ($data) {
            $user_id = $data['patient_id'];
            $requestData = $data;          
            $requestData['user_id'] = $user_id;
            if($VitalSign->save($requestData)){
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => false
                    );
            }else{
                 $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
            }
        }
        else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();
    }
    public function updatevitalSign() {
        $VitalSign = ClassRegistry::init('VitalSign');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            
        if ($data) {
            $user_id = $data['patient_id'];
            $requestData = $data;
            $id =$data['vitalsign_id'];
            
                $VitalSign->id = $id;
        
            $requestData['user_id'] = $user_id;
            $updateVitalsign=$VitalSign->save($requestData);
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => false
                    );

        } else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();
    }
     public function patientCommunications() {
        $Communication = ClassRegistry::init('Communication');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            if($data){
                $user_id = $data['patient_id'];                   
                
                $Communication->recursive = -1;
                $this->paginate = (array(
                    // 'fields'=>array('Communication.id','Communication.reciever_user_id'),
                    'conditions' => array('OR' => array('Communication.user_id' => $user_id, 'FIND_IN_SET(' . $user_id . ',Communication.reciever_user_id)'), 'Communication.parent_id' => 0, 'Communication.message_type' => 1),
                    'order' => 'Communication.id DESC',
                   
                        // 'recursive'=>-1
                ));
                $communications = $Communication->find('all', $this->paginate);
                //debug($communications);die;
                 $message = array(
                        'message' => __('success'),
                        'data' => $communications,
                        'status' => true
                    );
                 }else{
                    $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );

                 }
                 echo json_encode($message);
                 exit();
        
    }

    public function patientsLikeMe() {
        $MedicalCondition = ClassRegistry::init('MedicalCondition');
        $MedicalHistory = ClassRegistry::init('MedicalHistory');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
              $conditions = array();
               $user_id = $data['patient_id']; 
              $conditions['MedicalHistory.user_id NOT']=$user_id;
           
               // debug($this->request->data);die;
                 if(($data['conditions']!=0)){
                      $conditions['MedicalHistory.conditions']=$data['conditions'];
                }
              
                if(!empty($data['search_text'])){
                   $conditions['MedicalHistory.description LIKE']="%".$data['search_text']."%"; 
                }
           
           //debug($conditions);die;
            
            $MedicalHistory->recursive = -1;
            
            //$medicalConditionList = array();
            $medicalConditionList = $MedicalCondition->find('list', array('fields' => array('MedicalCondition.id', 'MedicalCondition.name')));
            
            $MedicalHistory->recursive = 0;
            $this->paginate = array(
                'conditions' => $conditions,
                'order' => 'MedicalHistory.id DESC',
                'group' => 'MedicalHistory.user_id',
                
            );
            $patientListings =$MedicalHistory->find('all', $this->paginate);
            $message = array(
                        'message' => __('success'),
                        'data' => $patientListings,
                        'status' => true
                    );
         // debug($patientListings);
            
        }
        else{
            $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
            );
        }
        echo json_encode($message);
        exit();

    }
    public function listTreatment() {
        $TreatmentHistory = ClassRegistry::init('TreatmentHistory');
        $Procedure = ClassRegistry::init('Procedure');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $id = $data['patient_id'];
            $TreatmentHistory->recursive = 1;
            $this->paginate = array(
                'conditions' => array('TreatmentHistory.user_id' => $id),
                'order' => array(
                    'TreatmentHistory.id' => 'desc'
                )
            );
            $treatmentHistories =$TreatmentHistory ->find('all', $this->paginate);
            foreach ($treatmentHistories as $key => $value) {
                unset($treatmentHistories[$key]['User']);
            }
            $procedures = $Procedure->find('list');
            $message = array(
                        'message' => __('success'),
                        'data' => $treatmentHistories,
                        'procedures' => $procedures,
                        'data' => $treatmentHistories,
                        'status' => true
            );
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
    }

    public function addTreatment() {
        $TreatmentHistory = ClassRegistry::init('TreatmentHistory');
        $Appointment = ClassRegistry::init('Appointment');
        $Procedure = ClassRegistry::init('Procedure');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        $id=$data['patient_id'];      
        
        $procedures = $Procedure->find('list');
        $TreatmentHistory->recursive = -1;
        $options = array('conditions' => array('Appointment.user_id' => $id, 'NOT' => array('Appointment.status' => 3)));
        $appointments = $Appointment->find('list', $options);
        foreach ($appointments as $key => $value) {
            $appointments[$key] = 'App-'.$value;
        }
        if ($data) {
            $addData =$data;

            $TreatmentHistory->create();
            $addData['start_date'] = date("Y-m-d H:i:s",strtotime($addData['start_date']));
            if(!empty($addData['end_date'])){
                $addData['end_date'] = date("Y-m-d H:i:s",strtotime($addData['end_date']));
            }
            $addData['parent_treatment'] = '0';
            $addData['user_id'] = $id;
            $flag = $TreatmentHistory->saveAssociated($addData);
            $message = array(
                        'message' => __('success'),
                        'data' => $flag,
                        'status' => true
            );
            
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
        
       
    }
    public function updateTreatment() {
        $TreatmentHistory = ClassRegistry::init('TreatmentHistory');
        $Appointment = ClassRegistry::init('Appointment');
        $Procedure = ClassRegistry::init('Procedure');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        $id=$data['patient_id'];     
        
        $procedures = $Procedure->find('list');
        $TreatmentHistory->recursive = -1;
        $options = array('conditions' => array('Appointment.user_id' => $id, 'NOT' => array('Appointment.status' => 3)));
        $appointments = $Appointment->find('list', $options);
        foreach ($appointments as $key => $value) {
            $appointments[$key] = 'App-'.$value;
        }
        if ($data) {
            $addData =$data;

            $addData['start_date'] = date("Y-m-d H:i:s",strtotime($addData['start_date']));
            if(!empty($addData['end_date'])){
                $addData['end_date'] = date("Y-m-d H:i:s",strtotime($addData['end_date']));
            }
            $addData['parent_treatment'] = '0';
            $addData['id'] = $data['treatment_id'];
            $addData['user_id'] = $id;
            $flag = $TreatmentHistory->saveAssociated($addData);
            $message = array(
                        'message' => __('success'),
                        'data' => $flag,
                        'status' => true
            );
            
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
        
       
    }
    public function listDietPlan() { 
        $DietPlan = ClassRegistry::init('DietPlan');
        $DietPlansDetail = ClassRegistry::init('DietPlansDetail');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);        
        //$id=          
        $DietPlan->recursive = 0;
        Configure::load('feish');
        $weekdays = Configure::read('feish.weekdays');        
        $dietPlans = $DietPlan->find('all',array('conditions'=>array('DietPlan.user_id'=>$data['patient_id'])));
        $i=0;
        foreach ($dietPlans as $key => $value) {
            $response[$i]['DietPlan'] = $value['DietPlan'];
            $dietPlansDetail = $DietPlansDetail->find('all',array('conditions'=>array('DietPlansDetail.diet_plan_id'=>$value['DietPlan']['id'])));
            $response[$i]['DietPlansDetail'] = $dietPlansDetail;
            $i++;
        }
        if ($data) {
           
            $message = array(
                    'message' => __('success'),
                    'data' => $response,
                    'status' => true
            );

        }else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function addDietPlan() { 
        $DietPlan = ClassRegistry::init('DietPlan');
        $DietPlansDetail = ClassRegistry::init('DietPlansDetail');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);        
        //$id=          
        $DietPlan->recursive = 0;
        Configure::load('feish');
        $weekdays = Configure::read('feish.weekdays');        

        if ($data) {
            $login_userId = $data['patient_id'];
            $data['start_date'] = date('Y-m-d',  strtotime($data['start_date']));
            $data['end_date'] = date('Y-m-d',  strtotime($data['end_date']));
            $data['added_by'] = $login_userId;
            $data['user_id'] = $login_userId;

            $DietPlan->create();
            if ($DietPlan->save($data)) {

                $last_insert_id = $DietPlan->id;
                $data['diet_plan_id'] = $last_insert_id;
                $PlanDetails = $data['PlanDetails'];

                foreach ($PlanDetails as $value) {
                    $DietPlansDetail->create();
                    $diet_arr = array('diet_plan_id' => $data['diet_plan_id'], 'weekday' => $value['weekday'], 'time' => date('H:i:s', strtotime($value['time'])), 'description' => $value['description'], 'created_date' => date('Y-m-d i:m:s'));
                    $flag=$DietPlansDetail->save($diet_arr);

                }


                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                );

               
            } else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );
               
            }
        }else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function UpdateDietPlan() { 
        $DietPlan = ClassRegistry::init('DietPlan');
        $DietPlansDetail = ClassRegistry::init('DietPlansDetail');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);        
        //$id=          
        $DietPlan->recursive = 0;
        Configure::load('feish');
        $weekdays = Configure::read('feish.weekdays');        

        if ($data) {
            $login_userId = $data['patient_id'];
            $data['start_date'] = date('Y-m-d',  strtotime($data['start_date']));
            $data['end_date'] = date('Y-m-d',  strtotime($data['end_date']));
            $data['added_by'] = $login_userId;
            $data['user_id'] = $login_userId;
            $DietPlan->id=$data['diet_id'];
            
            if ($DietPlan->save($data)) {
                
                $PlanDetails = $data['PlanDetails'];

                foreach ($PlanDetails as $value) {
                    $DietPlansDetail->id= $value['id'];                    
                    $diet_arr = array('diet_plan_id' => $value['diet_plan_id'], 'weekday' => $value['weekday'], 'time' => date('H:i:s', strtotime($value['time'])), 'description' => $value['description'], 'created_date' => date('Y-m-d i:m:s'));
                    $DietPlansDetail->save($diet_arr);

                }


                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                );

               
            } else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );
               
            }
        }else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function deleteDietPlan() {         
        $DietPlansDetail = ClassRegistry::init('DietPlansDetail');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true); 
        if($data){
            $DietPlansDetail->patient_id = $data['patient_id'];
            $DietPlansDetail->id = $data['id']; 
            $DietPlansDetail->delete();                
            $message = array(
                    'message' => __('success'),
                    'data' => true,
                    'status' => true
                );
        } else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();   
    }
   /* public function addFamilyHistory() {
         $FamilyHistory = ClassRegistry::init('FamilyHistory');
        $Relationship = ClassRegistry::init('Relationship');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);

        $FamilyHistory->recursive = 0;        
       

        if ($data) {
            if ($data['patient_id'] != "") {
               
                    $FamilyHistory->create();
                 $data['added_by']=$data['patient_id'];
                $data['updated_by']=$data['patient_id'];
                $data['user_id']=$data['patient_id'];
                if ($FamilyHistory->save($data)) { 
                        $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }*/

    public function listFamilyHistory() {
         $FamilyHistory = ClassRegistry::init('FamilyHistory');
        $Relationship = ClassRegistry::init('Relationship');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        $FamilyHistory->recursive = 0;        
       
        if ($data) {

            $data['added_by']=$data['patient_id'];
            $data['updated_by']=$data['patient_id'];
            $data['user_id']=$data['patient_id'];
            
            $this->paginate = array(
                'conditions' => array('FamilyHistory.user_id' =>$data['user_id']),
                'order' => 'FamilyHistory.id DESC',
                
            );
       
            $userFamilyHistory = $FamilyHistory->find('all', $this->paginate);
            foreach ($userFamilyHistory as $key => $value) {
                unset($userFamilyHistory[$key]['FamilyHistory']['member_name']);
                unset($userFamilyHistory[$key]['FamilyHistory']['relationship_id']);
                unset($userFamilyHistory[$key]['FamilyHistory']['age']);
                unset($userFamilyHistory[$key]['FamilyHistory']['disease_id']);
                unset($userFamilyHistory[$key]['FamilyHistory']['current_status']);
                unset($userFamilyHistory[$key]['FamilyHistory']['year']);
                unset($userFamilyHistory[$key]['FamilyHistory']['description']);
            }
            $relationships = $Relationship->find('list');
            $message = array(
                'message' =>__('success'),
                'status' => true,
                'data' => $userFamilyHistory,
                'relationships' => $relationships
                );                         
   
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function listMadicalHistory() {
        $MedicalHistory = ClassRegistry::init('MedicalHistory');
        $MedicalCondition = ClassRegistry::init('MedicalCondition');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        $MedicalHistory->recursive = 0;        
       
        if ($data) {
            $data['added_by']=$data['patient_id'];
            $data['updated_by']=$data['patient_id'];
            $data['user_id']=$data['patient_id'];
            
            $this->paginate = array(
                'conditions' => array('MedicalHistory.user_id' =>  $data['user_id']),
                'order' => 'MedicalHistory.id DESC',
                'fields' => array('MedicalHistory.id', 'MedicalHistory.conditions', 'MedicalHistory.condition_type', 'MedicalHistory.mh_date', 'MedicalHistory.current_medication', 'MedicalHistory.description', 'MedicalCondition.name')
                
            );
            $conditions[6] = 'Nephritis';
            $conditions[9] = 'Coronary artery disease';
            $conditions[10] = 'Hypo plastic kidney';
            $conditions[13] = 'Allergy';
            $userMedicalHistory = $MedicalHistory->find('all', $this->paginate);
            foreach ($userMedicalHistory as $key => $value) {
                $userMedicalHistory[$key]['MedicalHistory']['mh_date'] = strtotime($value['MedicalHistory']['mh_date']) * 1000;
            }
            $message = array(
            'message' =>__('success'),
            'status' => true,
            'data' => $userMedicalHistory,
            'conditions' => $conditions,
            );
        }
        else {
            $message = array(
                    'message' => __('empty'),
                    'status' => true,
                    'data' => (object)null
            ); 
         }
        echo json_encode($message);
        exit();
    }

    public function addMadicalHistory() {
        $MedicalHistory = ClassRegistry::init('MedicalHistory');
        $MedicalCondition = ClassRegistry::init('MedicalCondition');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);

        $MedicalHistory->recursive = 0;        
       

        if ($data) {
            if ($data['patient_id'] != "") {
               
                    $MedicalHistory->create();
                 
                $data['user_id']=$data['patient_id'];
                if ($MedicalHistory->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }

    public function updateMadicalHistory() {
        $MedicalHistory = ClassRegistry::init('MedicalHistory');        
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);

        $MedicalHistory->recursive = 0;        
       

        if ($data) {
            if ($data['patient_id'] != "") {            
                   
                $MedicalHistory->id =$data['madicalhistory_id'];
                $data['user_id']=$data['patient_id'];
                if ($MedicalHistory->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }


    public function listLabResult() {
        $LabTestResult = ClassRegistry::init('LabTestResult');
        $Test = ClassRegistry::init('Test'); 
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);        
        $LabTestResult->recursive = 0;        
       

        if ($data) {
               
                
            $data['added_by']=$data['patient_id'];
            $data['updated_by']=$data['patient_id'];
            $data['user_id']=$data['patient_id'];               
            
            $this->paginate = array(
                'conditions' => array('LabTestResult.user_id' =>  $data['user_id']),
                'order' => 'LabTestResult.id DESC',
                
            );
                   
            $userLabTestResult = $LabTestResult->find('all', $this->paginate);
            
                
                        $message = array(
                        'message' => __('success'),
                        'status' => true,
                        'data' => $userLabTestResult
                        );                          
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }

    public function addLabResult() {

        $LabTestResult = ClassRegistry::init('LabTestResult');
        $Test = ClassRegistry::init('Test'); 
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);

        $LabTestResult->recursive = 0;    
       

        
             if($_FILES['report_img']){
                $avatar = $_FILES['report_img'];
            $avatar_name = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 7);
            $upload_path = APP . WEBROOT_DIR . '/img/lab_test_reports/';
            $alias_name = $_FILES['report_img']['name'];

            if ($avatar['error'] =='0') {

                $ext = pathinfo($avatar["name"], PATHINFO_EXTENSION);
                if (strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg' || strtolower($ext) == 'png' || strtolower($ext) == 'docx' || strtolower($ext) == 'doc') {
                    if (move_uploaded_file($avatar['tmp_name'], $upload_path . DS . $avatar_name . "." . $ext)) {
                        $alias_name = $avatar_name . "." . $ext;
                    }
                }
            }
            $data['report'] = @$alias_name;
                 $data['patient_id']=$this->request->data('patient_id');
               }
            if ($data['patient_id'] != "") {
               
                    $LabTestResult->create();
                 
                $data['added_by']=$data['patient_id'];                
                $data['user_id']=$data['patient_id'];   
                if ($LabTestResult->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function updateLabResult() {
        $LabTestResult = ClassRegistry::init('LabTestResult');
        $Test = ClassRegistry::init('Test'); 
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);

        $LabTestResult->recursive = 0;        
       

        if(isset($_FILES['report_img'])){
                $avatar = $_FILES['report_img'];
            $avatar_name = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 7);
            $upload_path = APP . WEBROOT_DIR . '/img/lab_test_reports/';
            $alias_name = $_FILES['report_img']['name'];

            if ($avatar['error'] =='0') {
                
                $ext = pathinfo($avatar["name"], PATHINFO_EXTENSION);
                if (strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg' || strtolower($ext) == 'png' || strtolower($ext) == 'docx' || strtolower($ext) == 'doc') {
                    if (move_uploaded_file($avatar['tmp_name'], $upload_path . DS . $avatar_name . "." . $ext)) {
                        $alias_name = $avatar_name . "." . $ext;
                    }
                }
            }
            $data['report'] = @$alias_name;
                 $data['patient_id']=$this->request->data('patient_id');
             }
            if ($data['patient_id'] != "") {               
                $data['added_by']=$data['patient_id'];                
                $data['user_id']=$data['patient_id'];   
                $LabTestResult->id=$data['labresult_id'];
                if ($LabTestResult->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function uploadProfilePic()
	{          
         $avatar = $_FILES['profile_pic'];
            $avatar_name = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 7);
            $upload_path = APP . WEBROOT_DIR . '/img/user_avtar';
            $alias_name = $_FILES['profile_pic']['name'];

            if ($avatar['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($avatar["name"], PATHINFO_EXTENSION);
                if (strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg' || strtolower($ext) == 'png' || strtolower($ext) == 'bmp') {
                    if (move_uploaded_file($avatar['tmp_name'], $upload_path . DS . $avatar_name . "." . $ext)) {
                        $alias_name = $avatar_name . "." . $ext;
                    }
                }
            }
            $data['User']['avatar'] = @$alias_name;
            $data['User']['id']=$this->request->data('patient_id');           
            $this->User->id= $data['User']['id'];
             if ($this->User->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
            }     

       echo json_encode($message);
        exit();

        

    }

    public function addPatientFamilyHistory() {
        $FamilyHistory = ClassRegistry::init('FamilyHistory'); 
        $headers = apache_request_headers();

        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if ($data) {
            if ($data['patient_id'] != "") {
               
                    $FamilyHistory->create();
                 
                $data['added_by']=$data['patient_id'];
                
                $data['user_id']=$data['patient_id'];   
                if ($FamilyHistory->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function updatePatientFamilyHistory() {
        $FamilyHistory = ClassRegistry::init('FamilyHistory');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            
        if ($data) {
            $user_id = $data['patient_id'];
            $requestData = $data;
            $id =$data['family_history_id'];
            $FamilyHistory->id = $id;        
            $requestData['user_id'] = $user_id;
            $requestData['updated_by'] = $user_id;
            $updateFamilyHistory=$FamilyHistory->save($requestData);
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                    );

        } else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();
    }
     public function addPatientHealthRecord() {
        $PatientHabit = ClassRegistry::init('PatientHabit'); 
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);       
       
        if ($data) {
            if ($data['patient_id'] != "") {
                $habits=$data['habits'];
                foreach ($habits as $key => $value) {
                    $savedata=array();
                    $savedata['user_id']=$data['patient_id'];
                    $savedata['habit_id']=$habits[$key]['habit_id'];
                    $savedata['frequency']=$habits[$key]['frequency'];
                    $savedata['unit']=$habits[$key]['unit'];
                    $savedata['time_period']=$habits[$key]['time_period'];
                    $savedata['habit_since']=$habits[$key]['habit_since'];
                    $savedata['is_stopped']=$habits[$key]['is_stopped'];
                    $savedata['stopped_date']=$habits[$key]['stopped_date'];
                    $savedata['added_by']=$data['patient_id'];
                    $PatientHabit->create();
                    $habit=$PatientHabit->save($savedata);                  
                  }                

                if ($habit) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
    public function updatePatientHealthRecord() {
        $PatientHabit = ClassRegistry::init('PatientHabit');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
            
        if ($data) {
            $user_id = $data['patient_id'];
            $habits=$data['habits'];
                foreach ($habits as $key => $value) {
                    $savedata=array();
                    $PatientHabit->id = $habits[$key]['id']; 
                    $savedata['user_id']=$data['patient_id'];
                    $savedata['habit_id']=$habits[$key]['habit_id'];
                    $savedata['frequency']=$habits[$key]['frequency'];
                    $savedata['unit']=$habits[$key]['unit'];
                    $savedata['time_period']=$habits[$key]['time_period'];
                    $savedata['habit_since']=$habits[$key]['habit_since'];
                    $savedata['is_stopped']=$habits[$key]['is_stopped'];
                    $savedata['stopped_date']=$habits[$key]['stopped_date'];
                    $savedata['added_by']=$data['patient_id'];                    
                    $habit=$PatientHabit->save($savedata);                  
                  }    
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                    );

        } else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();
    }
     public function listPatientHealthParameter() { 
        $PatientHabit = ClassRegistry::init('PatientHabit');
        $LoginDetail = ClassRegistry::init('LoginDetail');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
         
         Configure::load('feish');

        $gender = Configure::read('feish.gender');
        if($data){
            $user_id = $data['patient_id'];
            $options = array(
                'conditions' =>
                        array(
                               'User.' . $PatientHabit->User->primaryKey =>$user_id,
                            ),
                );       
            $PatientHabit->recursive = 0;            
            //$this->PatientHabit->unbindModel(array('$belongsTo' => array('User','Habit')), true);
            $PatientHabitlist = $PatientHabit->find('all',$options);
           
            $message = array(
                            'message' => __('success'),
                            'data' => $PatientHabitlist,
                            'status' => true
                        );
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );

        }
        echo json_encode($message);
        exit();
    }
        
    public function deletePatientHealthRecord() {
        $PatientHabit = ClassRegistry::init('PatientHabit');
         $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
                        
        if ($data) {         
           
                $PatientHabit->id = $data['id']; 
                $PatientHabit->delete();                
                $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
                    );
        } else{
             $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                    );
        }
        echo json_encode($message);
                exit();
    }
    public function listAssistant() {
        $DoctorAssistant = ClassRegistry::init('DoctorAssistant');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $id = $data['doctor_id'];            
            $DoctorAssistant->recursive = -1;
            $this->User->recursive = -1;
            $this->paginate = array(
                'conditions' => array('User.added_by_doctor_id' => $id,'User.user_type'=>'4')                        
            );
            $DoctorAssistantList =$this->User->find('all', $this->paginate);
            /*foreach ($DoctorAssistant as $key => $value) {
                unset($DoctorAssistant[$key]['User']);
            }*/
            $message = array(
                        'message' => __('success'),
                        'data' => $DoctorAssistantList,
                        'status' => true
            );
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
    }
    public function addAssistant() {
        $DoctorAssistant = ClassRegistry::init('DoctorAssistant');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $doctor_id = $data['doctor_id'];            
             $this->User->create();            
            $service_id[] = $data['service_id'];
            $service_ids = $service_id[0];
            $data['added_by_doctor_id'] = $doctor_id;            
            $data['is_verified'] = 0;            
            if ($this->User->save($data)) {
                $last_insert_id = $this->User->id;
                $registerd_id = $last_insert_id;
                $registration_no = substr(str_shuffle(str_repeat('0123456789', 5)), 0, 7) . $registerd_id;
                $this->User->updateAll(array('User.registration_no' => "'" . $registration_no . "'"), array('User.id' => $registerd_id));
                if ($service_ids != "") {
                    foreach ($service_ids as $key => $value) {                        
                        $DoctorAssistant->create();
                        $assitant_arr = array('user_id' => $last_insert_id, 'service_id' =>$service_ids[$key]['id'], 'doctor_id' => $doctor_id);
                        $DoctorAssistant->save($assitant_arr);
                    }
                }
            }

            /*foreach ($DoctorAssistant as $key => $value) {
                unset($DoctorAssistant[$key]['User']);
            }*/
            $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
            );
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
    }
    public function editAssistant() {
        $DoctorAssistant = ClassRegistry::init('DoctorAssistant');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $doctor_id = $data['doctor_id'];            
            $this->User->id=$data['assistant_user_id'];              
            $service_id[] = $data['service_id'];
            $service_ids = $service_id[0];
            $data['added_by_doctor_id'] = $doctor_id;            
            $data['is_verified'] = 0;            
            if ($this->User->save($data)) {
                $last_insert_id = $this->User->id;
                $registerd_id = $last_insert_id;                
                if ($service_ids != "") {
                    foreach ($service_ids as $key => $value) {                        
                        $DoctorAssistant->id=$service_ids[$key]['assistant_id'];
                        $assitant_arr = array('user_id' => $last_insert_id, 'service_id' =>$service_ids[$key]['id'], 'doctor_id' => $doctor_id);
                        $DoctorAssistant->save($assitant_arr);
                    }
                }
            }

            /*foreach ($DoctorAssistant as $key => $value) {
                unset($DoctorAssistant[$key]['User']);
            }*/
            $message = array(
                        'message' => __('success'),
                        'data' => true,
                        'status' => true
            );
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();
    }
    public function activeDeactive() {
        $DoctorAssistant = ClassRegistry::init('DoctorAssistant');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            if($data['is_active']=='1'){
                $this->User->id=$data['user_id'];
                $this->User->save($data);
                $message = array(
                        'message' => __('success'),
                        'data' => 'Activated',
                        'status' => true
                );
            }
            elseif($data['is_active']=='0'){
                $this->User->id=$data['user_id'];
                $this->User->save($data);
                $message = array(
                        'message' => __('success'),
                        'data' => 'DeActivated',
                        'status' => true
                );
            }
            
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();

    }

    public function deleteRestore() {
        $DoctorAssistant = ClassRegistry::init('DoctorAssistant');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            if($data['is_deleted']=='1'){
                $this->User->id=$data['user_id'];
                $this->User->save($data);
                $message = array(
                        'message' => __('success'),
                        'data' => 'Restored',
                        'status' => true
                 );
            }
            elseif($data['is_deleted']=='0'){
                $this->User->id=$data['user_id'];
                $this->User->save($data);
                $message = array(
                        'message' => __('success'),
                        'data' => 'Deleted',
                        'status' => true
                );
            }
           
        }else{
            $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
            );

        }
        echo json_encode($message);
        exit();

    }
    public function addBookAppointment() {
        $Appointment = ClassRegistry::init('Appointment');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        
        if ($data) {
            if ($data['patient_id'] != "") {
               
                $Appointment->create();
                $data['scheduled_date'] = date("Y-m-d",strtotime($data['scheduled_date'])); 
                $data['status'] =0;               
                
                $data['user_id']=$data['patient_id'];   
                if ($Appointment->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }

    public function giveFeedbackToDoctor(){
        $Review = ClassRegistry::init('Review');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
       
        if ($data) {
            if ($data['patient_id'] != "") {
               
                $Review->create();               
                
                $data['user_id']=$data['patient_id'];   
                if ($Review->save($data)) { 
                        $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }           
            
            }else {
                    $message = array(
                            'message' => __('empty'),
                            'status' => true,
                            'data' => (object)null
                    );
                   
            }
        }
        else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }

     public function communicateToDoctor(){
        $Communication = ClassRegistry::init('Communication');
        $headers = apache_request_headers();
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);       
       
        if ($data) {
            $sendData =$data;
            $sendData['parent_id'] = 0;
            $sendData['is_viewed'] = 0;
            //$sendData['reciever_user_id']

            if (!empty($sendData['Attach_file']['name'])) {
                $sendData['is_attachment'] = 1;
                $sendData['uploaded_files'] = $Communication->upload_attachment($sendData['Attach_file'], 'attachements');
            } else {
                $sendData['is_attachment'] = 0;
            }
            $sendData['user_id'] = $data['patient_id'];
            $sendData['message_type'] = 2;
            $viewed_users = explode(',', $data['reciever_user_id']);
            //debug($viewed_users);
            foreach ($viewed_users as $user) {
                $user_array[$user] = 0;
            }
            // debug($user_array);die;
            $sendData['viewed_users'] = json_encode($user_array);            
            //debug($sendData); die;
            if ($Communication->save($sendData)) {
                $message = array(
                        'message' =>__('success'),
                        'status' => true,
                        'data' => true
                        );
                    } else {
                        $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                        );
                }               
        }else {
                $message = array(
                        'message' => __('empty'),
                        'status' => true,
                        'data' => (object)null
                );
               
         }
        echo json_encode($message);
        exit();
    }
   public function sendOTP() {
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $fetch_data = $this->User->find('first', array('fields'=>array('User.otp','User.email','User.mobile','User.user_type','User.salutation','User.first_name','User.last_name'),'conditions' => array('User.id' => $data['user_id'])));
            if($fetch_data){
                $otp = $fetch_data['User']['otp'];
                if($data['type'] == 'mobile'){
                    
                    $salutations = Configure::read('feish.salutations');

                    $number = $fetch_data['User']['mobile'];
                     
                    if ($fetch_data['User']['user_type'] == 2) {

                        $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . " " . $fetch_data['User']['last_name'] . $otp . ". your login OTP";
                    } elseif ($fetch_data['User']['user_type'] == 4) {
                        $sms_message = "Dear " . $salutations[$fetch_data['User']['salutation']] . ". " . $fetch_data['User']['first_name'] . $otp . ". your login OTP";
                    } else {
                        $sms_message = "";
                    }
                    $url = "http://bulksms.mysmsmantra.com/WebSMS/SMSAPI.jsp?username=feishtest&password=327407481&sendername=FEISHT&mobileno=" . $number . "&message=" . urlencode($sms_message);


                    $ch = curl_init($url);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,1); 
                    curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
                    $curl_scraped_page = curl_exec($ch);
                    curl_close($ch);

                }else{
                    $email = new CakeEmail();
                    $email->config('otp');
                    $email->from('info@feish.com');
                    $email->to($fetch_data['User']['email']);
                    //$email->viewVars(compact('fetch_data', 'verify_link', 'salutations', 'registration_no', 'password'));
                    $email->viewVars(compact('fetch_data','otp'));
                    $email->subject('Verify OTP');
                    $email->send();                 
                }
                $message = array(
                    'message' => __('success'),
                    'status' => true
                );
            }else{
                $message = array(
                    'message' => __('user not found'),
                    'status' => false
                );
            
            }
        }else{
            $message = array(
                    'message' => __('Invalid request past'),
                    'status' => false
                );
        }
        echo json_encode($message);exit();


   }



   
    
    /**
     * CHECK USER OTP WITH USER ID
     * @input params : userId, userToken
     * @output params : 
     * @return void
     * @throws NotFoundException When the view file could not be found
     *  or MissingViewException in debug mode.
     */
    public function checkOTP() {
        $User = ClassRegistry::init('User');
        $headers = apache_request_headers();

        $data = file_get_contents("php://input");
        $data =json_decode($data,true);       

        $checkUserToken = $User->find('count', array('conditions' => array('User.id' => $data['user_id'], 'User.otp' => $data['otp'])));
        if ($checkUserToken) {
            $message = array(
                'message' => __('success'),
                'status' => true,
            );
        } else {
            $message = array(
                    'message' => 'OTP is incorrect',
                    'status' => false
                );
        }
        echo json_encode($message);exit;
    }

   public function forgotPassword() {
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $fetch_data = $this->User->find('first', array('fields'=>array('User.id','User.otp','User.mobile','User.user_type','User.salutation','User.first_name','User.last_name'),'conditions' => array('User.email' => $data['email'])));
            if($fetch_data){
                $message = array(
                    'message' => __('success'),
                    'data' => array('user_id'=>$fetch_data['User']['id']),
                    'status' => true
                );
            }else{
                $message = array(
                    'message' => __('user not found'),
                    'status' => false
                );
            
            }
        }else{
            $message = array(
                    'message' => __('Invalid request past'),
                    'status' => false
                );
        }
        echo json_encode($message);exit();


   }

   public function resetPassword() {
        $data = file_get_contents("php://input");
        $data =json_decode($data,true);
        if($data){
            $fetch_data = $this->User->find('first', array('fields'=>array('User.otp','User.mobile','User.user_type','User.salutation','User.first_name','User.last_name'),'conditions' => array('User.id' => $data['user_id'])));
            if($fetch_data){
                $this->User->updateAll(array('User.password' => "'" . $this->Auth->password($data['password']) . "'"), array('User.id' => $data['user_id']));

                $message = array(
                    'message' => __('success'),
                    'status' => true
                );
            }else{
                $message = array(
                    'message' => __('user not found'),
                    'status' => false
                );
            
            }
        }else{
            $message = array(
                    'message' => __('Invalid request past'),
                    'status' => false
                );
        }
        echo json_encode($message);exit();


   }
   public function set_up_profile()
	{
		$user_detail= ClassRegistry::init('UserDetail');
		$headers = apache_request_headers();
		$data = file_get_contents("php://input");
        $data =json_decode($data,true);
		if(isset($_FILES['avatar']))
		$avatar = $_FILES['avatar'];
			if($data)
			{
				$user = $this->User->find('first',array(
                        'conditions'=>array(
                            'User.id'=>$data['id']))
                );
				if($user)
				{
				if(isset($data['first_name']) && $data['first_name']!="")
				{
					  $user['User']['first_name']=$data['first_name'];
				}
				if( isset($data['last_name']) &&$data['last_name']!="")
				{
					$user['User']['last_name']=$data['last_name'];
				}
				if( isset($data['birt_date']) &&$data['birth_date']!="")
				{
					$user['User']['birt_date']=date("Y-m-d",strtotime($data['birth_date']));
				}
				if(isset($data['qualification']) && $data['qualification']!="")
				{
					$user['User']['qualification']=$data['qualification'];
				}
				if( isset($data['facebook']) && $data['facebook']!="")
				{
					$user['User']['facebook']=$data['facebook'];
				}
				if(isset($data['google_plus']) && $data['google_plus']!="")
				{
					$user['User']['google_plus']=$data['google_plus'];
				}
				if( isset($data['twitter']) && $data['twitter']!="")
				{
					$user['User']['twitter']=$data['twitter'];
				}
				if(isset($data['address']) &&$data['address']!="")
				{
					$user['User']['address']=$data['address'];
				}
				if(isset($data['occupation_id']) &&$data['occupation_id']!="")
				{
					$user['User']['occupation_id']=$data['occupation_id'];
				}
				if(isset($data['ethnicity_id']) &&$data['ethnicity_id']!="")
				{
					$user['User']['ethnicity_id']=$data['ethnicity_id'];
				}
				if(isset($data['blood_group']) &&$data['blood_group']!="")
				{
					$user['User']['blood_group']=$data['blood_group'];
				}
				if(isset($data['gender']) &&$data['gender']!="")
				{
					$user['User']['gender']=$data['gender'];
				}
				if(isset($data['marital_status']) &&$data['marital_status']!="")
				{
					$user['User']['marital_status']=$data['marital_status'];
				}
				if( isset($data['state']) &&$data['state']!="")
				{
					$user['User']['state']=$data['state'];
				}
				if(isset($data['city'])&&$data['city']!="")
				{
					$user['User']['city']=$data['city'];
				}
				if(isset($data['height'])||isset($data['weight'])||isset($data['waist_size']))
				{
					$ud=array();
					if(isset($data['height']))
					{
					$ud['height']=$data['height'];
					}
				    if(isset($data['weight']))
					{
					$ud['weight']=$data['weight'];
					}
					if(isset($data['waist_size']))
					{
					$ud['waist_size']=$data['waist_size'];
					}
					$ud['user_id']=$data['id'];
					$check=$this->UserDetail->find('first',array(
                        'conditions'=>array(
                            'UserDetail.user_id'=>$data['id'])));
				if($check)
				{
					$ud['id']=$check['UserDetail']['id'];
					$user_detail->save($ud);
				}else{
					$user_detail->save($ud);
					}
					
					}
				if (isset($avatar))
				{
            $avatar_name = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 7);
            $upload_path = APP . WEBROOT_DIR . '/img/user_avtar';
            $alias_name = $_FILES['avatar']['name'];

            if ($avatar['error'] == UPLOAD_ERR_OK) {
                $ext = pathinfo($avatar["name"], PATHINFO_EXTENSION);
                if (strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg' || strtolower($ext) == 'png' || strtolower($ext) == 'bmp') {
                    if (move_uploaded_file($avatar['tmp_name'], $upload_path . DS . $avatar_name . "." . $ext)) {
                        $alias_name = $avatar_name . "." . $ext;
                    }
                }
            }
			$user['User']['avatar'] = @$alias_name;
				}
				if ($this->User->save($user))
		         {
					  //$response = $user['User'];
                    $message = array(
                        'message' => __('profile updated successfully'),
                        'status' => true,
						//'data'=>$response,
                    );
                }else{
                    $message = array(
                        'message' => __('profile not updated'),
                        'status' => true,
                    );
                }
				}else{
					$message=array(
					'message'=>_('Invalid id'),
					'status'=>false,
					);
					}
            }else{
				$message=array(
					'message'=>_('Invalid request'),
					'status'=>false,
					);
				}
            echo json_encode($message);
			exit();
       }
	   	
public function addService() {
            $Service = ClassRegistry::init('Service');
            $headers = apache_request_headers();
            $data = file_get_contents("php://input");
            $data =json_decode($data,true);
			if($data['title']!="" && $data['description']!="" && $data['address']!="" && $data['locality']!="" && $data['city']!="" && $data['pin_code']!="" && $data['phone']!="" && $data['user_id']!="" && $data['specialty_id']!="")
			{
				$user=$this->User->find('first',array(
                        'conditions'=>array(
                            'User.id'=>$data['user_id'])));
				if($user['User']['user_type']==2)
				{
					$data['is_type']="Doc";
					$data['service_type']="Paid";
				}
				if($user['User']['user_type']==6)
				{
					$data['is_type']="Lab";
					$data['service_type']="Free";
				}
				$address=$data['address'];
				$geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false');
   $geo = json_decode($geo, true);
   if ($geo['status'] = 'OK') {
      $data['latitude'] = $geo['results'][0]['geometry']['location']['lat'];
      $data['longitude'] = $geo['results'][0]['geometry']['location']['lng'];
	  			
				$data['prev_rating']=0.0;
				$data['avg_rating']=0.0;
				$data['review_count']=0;
				$data['is_active']=0;
				$data['is_deleted']=0;
				$data['view_counter']=0;
				$check="[";
				for($i=0;$i<strlen($data['specialty_id']);$i++)
				{
					$check=$check.$data['specialty_id'][$i].",";
				}
				$check=$check."a]";
				$data['specialty_id']=$check;
				$Service->create();
				if($Service->save($data))
				{$message=array(
					'message'=>_('Success'),
					'status'=>true,
					);}
				else{$message=array(
					'message'=>_('Error'),
					'status'=>false,
					);}
   }
   else{
	   $message=array(
					'message'=>_('Try again later'),
					'status'=>false,
					);
	   }}else{
				$message=array(
					'message'=>_('Incomplete or Invalid Details'),
					'status'=>false,
					);
				}
            echo json_encode($message);
			exit();
}


}
