                <div>
                   <img src="{$this->get_image('kuser-small')}" width="16" height="16" /> <a href="#" onclick="return userspopup('{CreateUrl('userselect')}', 'assigned_to')">
                   {L('select')}</a>
                   <br />
                   <textarea cols="10" rows="4" name="assigned_to" id="assigned_to"><?php
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
                            script: "{$baseurl}javascript/callbacks/usersearch.php?",
                            varname: "user",
                            delay:50,
                            timeout:5000,
                            minchars:2,
                            noresults:'{#L('noresults')}'
                        };
                        var as = new bsn.AutoSuggest('assigned_to', options);
                   </script>
				</div>
