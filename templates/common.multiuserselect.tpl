                <div>
                   <img src="{$this->get_image('kuser-small')}" width="16" height="16" alt="{L('selectuser')}" />
                   <a href="#" onclick="return userspopup('{$this->url('userselect', array('onlyassignees' => 1))}', '{(isset($id) ? $id : 'assigned_to')}')">
                   {L('select')}</a>
                   <br />
                   <textarea cols="10" rows="4" name="{(isset($id) ? $id : 'assigned_to')}" id="{(isset($id) ? $id : 'assigned_to')}"><?php
                   if (!Req::val('assigned_to')):
                   foreach ($userlist as $usr):
                   ?>{$usr}; <?php
                   endforeach;
                   else:
                   ?>{Req::val('assigned_to')}<?php
                   endif;
                   ?></textarea>
                   <script type="text/javascript">
                          var options = {
                            script: "{$this->relativeUrl($baseurl)}javascript/callbacks/usersearch.php?onlyassignees=1&",
                            varname: "user",
                            delay:50,
                            timeout:5000,
                            minchars:2,
                            noresults:'{#L('noresults')}'
                        };
                        var as = new bsn.AutoSuggest('assigned_to', options);
                   </script>
				</div>
