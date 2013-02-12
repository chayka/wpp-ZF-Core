<?php
require_once 'Zend/Filter/Input.php';

class Zend_Filter_Input_Advanced extends Zend_Filter_Input{
    
    public function applyMessages(array &$validators, array $messages=array()){
        if(!empty($validators)){
            $messageTemplates=array();
            $this->_initClassMessageTemplates('Zend_Filter_Input', $messageTemplates);
            
            foreach($validators as $name=>$rule){
//                print_r($rule);
                foreach($rule as $key=>$validator){
                    if(is_object($validator) 
                    && is_subclass_of($validator, 'Zend_Validate_Abstract')){
                        $validatorClass = get_class($validator);
                        
                        $this->_initClassMessageTemplates($validatorClass, $messageTemplates);
                        $this->setOptions($messageTemplates['Zend_Filter_Input']);
                      
                        if (isset($messageTemplates[$validatorClass])){
                            $messages[$validatorClass] = isset($messages[$validatorClass])?
                                                         array_merge($messageTemplates[$validatorClass], $messages[$validatorClass]):
                                                         $messageTemplates[$validatorClass];
                        }
                        if (isset($messages[$validatorClass])){
                            $validator->setMessages($messages[$validatorClass]);
                        }
                    }
                }
                
            }
        }
    }

    protected function _initClassMessageTemplates($validatorClass, &$messageTemplates){
        if(empty($messageTemplates[$validatorClass])){
            switch($validatorClass){
                case 'Zend_Validate_Alnum':
            		$messageTemplates['Zend_Validate_Alnum'] = array(
                        Zend_Validate_Alnum::NOT_ALNUM    => "������ ���� ����� ��������� ������ ����� � �����",
                        Zend_Validate_Alnum::STRING_EMPTY => "���� �� ���������"
                    );
            		break;
                case 'Zend_Validate_StringLength':
            		$messageTemplates['Zend_Validate_StringLength'] = array(
                        Zend_Validate_StringLength::TOO_SHORT => "����� �������� ��������� ����� %min% ��������",
                        Zend_Validate_StringLength::TOO_LONG  => "����� �������� ��������� %max% ��������"
                    );
            		break;
                case 'Zend_Validate_EmailAddress':
                    $messageTemplates['Zend_Validate_EmailAddress'] = array(
                        Zend_Validate_EmailAddress::INVALID            => "�������� '%value%' �� �������� ������������ e-mail ������� � ������� local-part@hostname",
                        Zend_Validate_EmailAddress::INVALID_HOSTNAME   => "'%hostname%' is not a valid hostname for email address '%value%'",
                        Zend_Validate_EmailAddress::INVALID_MX_RECORD  => "'%hostname%' does not appear to have a valid MX record for the email address '%value%'",
                        Zend_Validate_EmailAddress::DOT_ATOM           => "'%localPart%' not matched against dot-atom format",
                        Zend_Validate_EmailAddress::QUOTED_STRING      => "'%localPart%' not matched against quoted-string format",
                        Zend_Validate_EmailAddress::INVALID_LOCAL_PART => "'%localPart%' is not a valid local part for email address '%value%'"
                    );
            		break;
                case 'Zend_Validate_Between':
                    $messageTemplates['Zend_Validate_Between'] = array(
                        Zend_Validate_Between::NOT_BETWEEN        => "�������� '%value%' ������� �������� � �������� �� '%min%' �� '%max%', ������������",
                        Zend_Validate_Between::NOT_BETWEEN_STRICT => "�������� '%value%' ������ ���� ������ ������ '%min%' � ������ '%max%'"
            		);
            		break;
                case 'Zend_Filter_Input':
                    $messageTemplates['Zend_Filter_Input'] = array(
                        Zend_Filter_Input::MISSING_MESSAGE     => "���� ������ ���� ����������� ���������",//"Field '%field%' is required by rule '%rule%', but the field is missing",
                        Zend_Filter_Input::NOT_EMPTY_MESSAGE   => "���� ������ ���� ����������� ���������"//"You must give a non-empty value for field '%field%'"
            		);
            }
        }


    }
    
    protected function _process()
    {
        if ($this->_processed === false) {
            $this->_filter();
            foreach($this->_data as $key=>$value){
                if(empty($value)){
                    unset($this->_data[$key]);
                }
            }
            $this->_validate();
            $this->_processed = true;
        }
    }
    
}