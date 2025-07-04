<?php

namespace Modules\admin\Controllers; //Modules/admin/controller
use App\Controllers\BaseController;
use App\Models\ApplicationMain;
use App\Models\PlacementMain;
use Modules\admin\Models\ApplicationModel; //Modules/admin/Models


 class Dashboard_controller extends BaseController
 {

    public function __construct()
    {
        //$this->session = service('session');
        /*$this->application = model('Modules\admin\Models\ApplicationModel');
        $this->app_status = model('Modules\admin\Models\placement');*/
    }

    
    public function dashboard($student_id = null) {

        $data['test'] = '';
        $this->render('dashboard', $data);
    }

    public function senarai_pengguna($student_id = null) {
    
        $data['application_sts'] = $this->application->getUsers()->getResult();
        $this->render('senarai_pengguna', $data);
    }

    public function add_pengguna()
    {
        $data = [
            'user_name'   => '',
            'user_email'  => '',
            'user_role'   => '',
            'user_status' => '',
            'user_remark' => '',
            'error'       => [],
        ];

        $data['result_ppd'] = $this->application->getPPD()->getResult();       
        $data['result_sekolah'] = $this->application->getSchool()->getResult(); 

        if ($this->request->getPost()) {

            $data['user_name']   = $this->request->getPost('user_name');
            $data['user_email']  = $this->request->getPost('user_email');
            $data['user_role']   = $this->request->getPost('user_role');
            $data['user_status'] = $this->request->getPost('user_status');
            $data['user_remark'] = $this->request->getPost('user_remark');

            $rules = [
                "user_name" => [
                    "label" => "Nama Pengguna", 
                    "rules" => "required"
                ],
                "user_email" => [
                    "label" => "Emel", 
                    "rules" => "required|valid_email"
                ],
                "user_password" => [
                    "label" => "Katalaluan", 
                    "rules" => "required"
                ],
                "user_password2" => [
                    "label" => "Sah Katalaluan", 
                    "rules" => "required|matches[user_password]"
                ],
                "user_role" => [
                    "label" => "Aras Pengguna", 
                    "rules" => "required"
                ],

            ];

            if (in_array($data['user_role'], ['user', 'PPD'])) {
                $rules = [
                    "user_name" => [
                        "label" => "Nama Pengguna", 
                        "rules" => "required"
                    ],
                    "user_email" => [
                        "label" => "Emel", 
                        "rules" => "required|valid_email"
                    ],
                    "user_password" => [
                        "label" => "Katalaluan", 
                        "rules" => "required"
                    ],
                    "user_password2" => [
                        "label" => "Sah Katalaluan", 
                        "rules" => "required|matches[user_password]"
                    ],
                    "user_role" => [
                        "label" => "Aras Pengguna", 
                        "rules" => "required"
                    ],
                    "ppd_id" => [
                        "label" => "JPN/PPD", 
                        "rules" => "required"
                    ]

                ];
            }

            if ($data['user_role'] === 'Sekolah') {
                $rules = [
                    "user_name" => [
                        "label" => "Nama Pengguna", 
                        "rules" => "required"
                    ],
                    "user_email" => [
                        "label" => "Emel", 
                        "rules" => "required|valid_email"
                    ],
                    "user_password" => [
                        "label" => "Katalaluan", 
                        "rules" => "required"
                    ],
                    "user_password2" => [
                        "label" => "Sah Katalaluan", 
                        "rules" => "required|matches[user_password]"
                    ],
                    "user_role" => [
                        "label" => "Aras Pengguna", 
                        "rules" => "required"
                    ],
                    "school_id" => [
                        "label" => "Sekolah", 
                        "rules" => "required"
                    ],
                    "user_remark" => [
                        "label" => "Peranan Pengguna", 
                        "rules" => "required"
                    ]

                ];
            }

            if ($this->validate($rules)) {
                
                $userData = [
                    'user_name'     => $this->request->getPost('user_name'),
                    'user_email'    => $this->request->getPost('user_email'),
                    'user_role'     => $this->request->getPost('user_role'),
                    'user_status'   => $this->request->getPost('user_status') ?? 'Active',
                    'user_password' => password_hash($this->request->getPost('user_password'), PASSWORD_BCRYPT),
                ];

                if (in_array($userData['user_role'], ['user', 'PPD'])) {
                    $userData['ppd_id'] = $this->request->getPost('ppd_id');
                }

                if ($userData['user_role'] === 'Sekolah') {
                    $userData['school_id'] = $this->request->getPost('school_id');
                    $userData['user_remark'] = $this->request->getPost('user_remark');
                }

                if ($this->application->insertUser($userData)) {
                    return redirect()->to('/admin/senarai-pengguna')
                                     ->with('msg', 'Maklumat Pengguna Berjaya Disimpan');
                } else {
                    return redirect()->back()
                                     ->with('msg_error', 'Maklumat Pengguna Tidak Berjaya Disimpan')
                                     ->withInput();
                }
            } else {
                $data['error'] = $this->validator->getErrors();
                $data['old'] = $this->request->getPost();

            }
        }

        $this->admin('add_pengguna',$data);
    }

    public function edit_pengguna($id = null)
    {
        
        $data['result_ppd'] = $this->application->getPPD()->getResult();
        $data['result_sekolah'] = $this->application->getSchool()->getResult();
        $data['result'] = $this->application->getUsers($id)->getRow();
        $this->admin('edit_pengguna',$data);

    }

    public function kemaskini_pengguna()
    {
       
        if ($this->request->getPost()) {

            $rules = [
                "user_name" => [
                    "label" => "Nama Pengguna", 
                    "rules" => "required"
                ],
                "user_role" => [
                    "label" => "Aras Pengguna", 
                    "rules" => "required"
                ],

            ];

            $id = $this->request->getPost('id');

            if ($this->validate($rules)) {

                $data = [
                    'user_name'   => $this->request->getPost('user_name'),
                    'user_email'  => $this->request->getPost('user_email'),
                    'user_role'   => $this->request->getPost('user_role'),
                    'user_remark' => $this->request->getPost('user_remark'),
                    'user_status' => $this->request->getPost('user_status'),
                    'ppd_id'      => $this->request->getPost('ppd_id'),
                ];

                if ($this->request->getPost('user_password')) {
                    $data['user_password'] = password_hash($this->request->getPost('user_password'), PASSWORD_DEFAULT);
                }

                $update = $this->application->updateUser($id, $data);

                if($update){
                    $this->session->setFlashdata('msg', 'Maklumat Pengguna Berjaya Dikemaskini');
                }else{
                    $this->session->setFlashdata('msg_error', 'Maklumat Pengguna Tidak Berjaya Dikemaskini');
                }

                return redirect()->to('/admin/senarai-pengguna')->send();

            }else{

                $data['result_ppd'] = $this->application->getPPD()->getResult();
                $data['result_sekolah'] = $this->application->getSchool()->getResult();
                $data['result'] = $this->application->getUsers($id)->getRow();
                $data['error'] = $this->validator->getErrors();
            }

        }

        $this->admin('edit_pengguna',$data);

        
    }


 }