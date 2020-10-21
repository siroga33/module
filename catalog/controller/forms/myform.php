<?php

class ControllerFormsMyform extends Controller
{
    public function index()
    {
        if (($this->request->server['REQUEST_METHOD']) == 'POST') {
            $this->load->model('forms/myform');
            $form_id = $this->model_forms_myform->saveData($this->request->post);

            if ($form_id) {

                $this->response->redirect($this->url->link('forms/myform', 'token=' . $this->session->data['token'])); //Снова перезагружаем форму     

            }
        }

        $this->load->language('forms/myform');

        $this->document->setTitle($this->language->get('heading_title'));
        $data['breadcrumbs'] = array();

        $route = $this->request->get['route'];

        $data = array();
        $data['breadcrumbs'][] = array(
            'text'       => 'Home',
            'href'       =>  $this->url->link('common/home'),
            'separator'  => false
        );

        $data['form_heading'] = $this->language->get('heading_title');
        $data['first_value'] =  $this->language->get('text_firstname');
        $data['second_value'] = $this->language->get('text_secondname');
        $data['third_value'] =  $this->language->get('text_thirdname');
        $data['forth_value'] = 'Phone No:';

        $data['continue'] = $this->url->link('common/home');
        $data['button_continue'] = $this->language->get('button_save');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_botom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');



        $template = 'forms/myform.tpl';
        $this->response->setOutput($this->load->view($template, $data));
    }
}
