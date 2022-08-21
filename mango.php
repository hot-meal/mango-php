<?php
    class Mango {
        private $templateregex = '/\\{\\{(([@!]?)(.+?))\\}\\}(([\\s\\S]+?)(\\{\\{:\\1\\}\\}([\\s\\S]+?))?)\\{\\{\\/\\1\\}\\}/';
        private $resultregex = '/\\{\\{([=%])(.+?)\\}\\}/';

        private $vars = false;
        private $key = false;
        private $mango = '';
        private $result = '';

        public function __construct($template){
            $this->mango = $template;
        }

        public function clean($val){
            //useful to parse messages, emoji etc
            return htmlspecialchars($val.'', ENT_QUOTES);
        }

        public function expand_value($index) {            
            $index = explode('.', $index);

            return $this->search_value($index, $this->vars);
        }

        private function search_value($index, $value) {
            if(is_array($index) and
               count($index)) {
                $current_index = array_shift($index);
            }
            if(is_array($index) and
               count($index) and
               is_array($value[$current_index]) and
               count($value[$current_index])) {
                return $this->search_value($index, $value[$current_index]);
            } else {
                $val = isset($value[$current_index])?$value[$current_index]:'';
                return str_replace('{{', "{\f{", $val);
            }
        }

        public function matchTags($matches) {
            $_ = $matches[0];
            $__ = $matches[1];
            $meta = $matches[2];
            $key = $matches[3];
            $inner = $matches[4];
            $if_true = $matches[5];
            $has_else = $matches[6];
            $if_false = $matches[7];

            $val = $this->expand_value($key);

            $temp = "";
            $i;

            if (!$val) {
                if ($meta == '!') {
                    return $this->render($inner);
                }

                if ($has_else) {
                    return $this->render($if_false);
                }

                return "";
            }

           
            if (!$meta) {
                return $this->render($if_true);
            }

           
            if ($meta == '@') {
               
                $_ = $this->vars['_key'];
                $__ = $this->vars['_val'];
                
                foreach ($val as $i => $v) {
                    $this->vars['_key'] = $i;
                    $this->vars['_val'] = $v;

                    $temp .= $this->render($inner);
                }

                $this->vars['_key'] = $_;
                $this->vars['_val'] = $__;
                
                return $temp;
            }
        }

        public function replaceTags($matches) {
            $_ = $matches[0];
            $meta = $matches[1];
            $key = $matches[2];

            $val = $this->expand_value($key);

            if ($val || $val === 0) {
                return $meta == '%' ? $this->clean($val) : $val;
            }

            return "";
        }

        private function render($fragment) {
            $matchTags = preg_replace_callback($this->templateregex, array($this, "matchTags"), $fragment);
            $replaceTags = preg_replace_callback($this->resultregex, array($this, "replaceTags"), $matchTags);

            return $replaceTags;
        }

        public function parse($obj){
            $this->vars = $obj;

            return $this->render($this->mango);
        }
    }
?>
