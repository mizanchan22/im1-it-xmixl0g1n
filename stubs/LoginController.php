<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\Response;
use App\Models\Login_m;
use CodeIgniter\Controller;


class LoginController extends BaseController
{
    protected $loginAuthModel;

    public function __construct()
    {
        $this->loginAuthModel = new Login_m();
        date_default_timezone_set('Asia/Kuala_Lumpur');
    }

    public function index()
    {
        $data['msj'] = session()->getFlashdata('msj') ?? '';
        echo view('layouts/view_login', $data);
    }

    public function auth_login()
    {
        
        $request = service('request');

        if ($this->request->getPost('btn_submit')) {

            
            $validation = \Config\Services::validation();
            $validation->setRules([
                'txt_username' => 'required|trim',
                'txt_password' => 'required|trim'
            ]);

            
            if ($this->request->getPost()) {

                $p_Pengenalan = trim($this->request->getPost('txt_username'));
                $p_Katalaluan = trim($this->request->getPost('txt_password'));
                $encryptpassword = md5($p_Katalaluan);

                // dummy data
                $dummy_users = [
                    // letak ic korg: cth; 9999999999, tapi kena exist dlm ldap
                    'sini'
                ];

                $dummy_passwords = [''];

                if (in_array($p_Pengenalan, $dummy_users)) {
                    $key = array_search($p_Pengenalan, $dummy_users);
                    if ($dummy_passwords[$key] == $p_Katalaluan) {                        
                        return $this->authentication($p_Pengenalan);
                    }
                    else{
                        session()->setFlashdata('Mesej', "Kad Pengenalan atau Kata Laluan Yang Dimasukkan Tidak Betul. Sila cuba lagi");
                        return redirect()->to('login');
                    }
                }

               
                if ($validation->withRequest($this->request)->run()) {

                    // Semak Auth Staf
                    $ldapserver = "";
                    $ldapbase = "";
                    $username = $p_Pengenalan;
                    $password = $p_Katalaluan;
                    $userdn = '';

                    // Connect to LDAP service
                    $ldap = ldap_connect($ldapserver, 389);
                    if ($ldap === FALSE) {
                        die("Couldn't connect to LDAP service");
                    }

                    if ($ldap) {
                        // Bind as application
                        $ldapbind = ldap_bind($ldap);

                        if ($ldapbind) {
                            $query = "(&(icnumber=" . $username . ")(objectClass=person))";
                            $results = ldap_search($ldap, $ldapbase, $query);
                            $info = ldap_get_entries($ldap, $results);

                            if ($info === FALSE) {
                                session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Tidak Sah");
                                return redirect()->to('login');
                            }

                            if ((int) @$info['count'] > 0) {
                                $userdn = $info[0]['dn'];
                            }

                            if (trim((string) $userdn) == '') {
                                session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Tidak Sah");
                                return redirect()->to('login');
                            }

                            $r = ldap_compare($ldap, $userdn, 'userPassword', $password);

                            if ($r === -1) {
                                session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Tidak Sah");
                                return redirect()->to('login');
                            } elseif ($r === true) {
                                $noK = $info[0]["employeenumber"][0];
                                $mail = $info[0]["mail"][0] ?? "";
                                $cn_name = $info[0]["cn"][0];
                                $user_id = trim($info[0]["uid"][0]);
                                $ptj = $info[0]["ou"][0] ?? "";
                                $stesen = $info[0]["stesen"][0] ?? "";
                                $jawatan = $info[0]["personalTitle"][0] ?? "";
                                $kod_program = $info[0]["Program"][0] ?? "";

                                // Semak Kakitangan dalam users
                                $result = $this->loginAuthModel->authUser($username);

                                if ($result) { // Wujud dalam users
                                    $result_inteam = $this->loginAuthModel->authInteam($noK); // Semak INTEAM
                                    if ($result_inteam) { // Staf Tetap
                                        foreach ($result_inteam as $row) {
                                            $nama = $row->nama;
                                            $emel = $row->emel;
                                            $telpejabat = $row->telpejabat;
                                            $telbimbit = $row->telbimbit;
                                            $ptj = $row->kodpusat;
                                            $jawatan = $row->gelaranjawatan;
                                        }

                                        $salt = substr(md5(time()), 0, 6);
                                        $data_update = [
                                            'user_password' => md5($password . $salt),
                                            'user_salt' => $salt,
                                            'user_nama' => $nama,
                                            'user_emel' => $emel,                                                
                                            'user_jawatan' => strtoupper($jawatan),
                                            'user_last_login' => date('Y-m-d H:i:s'),
                                            'user_nok' => $noK,
                                            'user_ptj' => $ptj,
                                        ];

                                        $this->loginAuthModel->_update_user($username, $data_update);

                                    } else { // Staf Kontrak
                                        $salt = substr(md5(time()), 0, 6);
                                        $data_update = [
                                            'user_password' => md5($password . $salt),
                                            'user_salt' => $salt,
                                            'user_nama' => $cn_name,
                                            'user_emel' => $mail,                                                
                                            'user_last_login' => date('Y-m-d H:i:s'),
                                            'user_nok' => $noK,
                                            'user_ptj' => $ptj,
                                        ];

                                        $this->loginAuthModel->_update_user($username, $data_update);
                                    }

                                    return $this->authentication($username);

                                } else { // Tidak Wujud dalam users
                                    session()->setFlashdata('Mesej', "Anda Tidak Dibenarkan Untuk Akses Sistem Ini");
                                    return redirect()->to('login');
                                }


                            } elseif ($r === false) { // password salah
                                session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Tidak Sah");
                                return redirect()->to('login');
                            }
                        } else { // if ldapbind failed
                            session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Tidak Sah");
                            return redirect()->to('login');
                        }
                    }
                    
                } else {
                    session()->setFlashdata('Mesej', "Sila Masukkan ID Pengenalan Dan Kata Laluan Anda");
                    return redirect()->to('login');
                }
            }
        }
    }

    public function authentication($no_ic)
    {
        $session = service('session');
        $result = $this->loginAuthModel->authUser($no_ic);

        if ($result) {
            foreach ($result as $row) {                
                $r_IdPengguna = $row->id;
                $r_Pengenalan = $row->user_id;
                $r_NoK = $row->user_nok;
                $r_Nama = $row->user_nama;
                $r_Jawatan = $row->user_jawatan;
                $r_Ptj = $row->user_ptj;
                $r_Status = $row->user_status;
                $r_JenisPengguna = $row->user_role;
            }

            if ($r_Pengenalan != "") {
                $sess_array = [
                    's_IdPengguna' => $r_IdPengguna,
                    's_KP' => $r_Pengenalan,
                    's_NoK' => $r_NoK,
                    's_Nama' => $r_Nama,
                    's_Jawatan' => $r_Jawatan,
                    's_Ptj' => $r_Ptj,
                    's_Status' => $r_Status,
                    's_JenisPengguna' => $r_JenisPengguna,              
                    's_Password' => $this->request->getPost('txt_password'),       
                ];

                $session->set($sess_array);

                if ($r_Status != "AKTIF") {
                    session()->setFlashdata('Mesej', "Log Masuk Anda Tidak Aktif.Sila Hubungi Pentadbir Sistem");
                    return redirect()->to('login');
                } else {
                    $data_update = [                        
                        'user_last_login' => date('Y-m-d H:i:s')                        
                    ];
                    $this->loginAuthModel->_update_user($r_IdPengguna, $data_update);

                    $role = $session->get('s_JenisPengguna');

                    if ($role === 'PENTADBIR') {
                       $redirect_url = $session->get('redirect_url') ?: 'admin/dashboard'; //redirect to admin/dashboard
                        $session->remove('redirect_url');
                        return redirect()->to($redirect_url);

                    }elseif ($role === 'STAF'){
                        $redirect_url = $session->get('redirect_url') ?: 'staf/dashboard'; //redirect to staf/dashboard
                        $session->remove('redirect_url');
                        return redirect()->to($redirect_url);
                    }


                }
            }

        } else {
            session()->setFlashdata('Mesej', "ID Pengenalan Atau Kata Laluan Anda Tidak Sah");
            return redirect()->to('login');
        }
    }

    public function encryptIt($q)
    {
        $cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
        return base64_encode(openssl_encrypt($q, 'AES-256-CBC', md5($cryptKey), 0, md5(md5($cryptKey))));
    }

    public function decryptIt($q)
    {
        $cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
        return rtrim(openssl_decrypt(base64_decode($q), 'AES-256-CBC', md5($cryptKey), 0, md5(md5($cryptKey))), "\0");
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('login');
    }

    public function send_email($send)
    {
        $email = \Config\Services::email();
        $email->setFrom($send['from']);
        $email->setTo($send['to']);
        $email->setSubject($send['subject']);
        $email->setMessage($send['msg']);
        $email->send();
    }
}