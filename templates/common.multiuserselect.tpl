                <div>
                   <img src="{$this->get_image('kuser-small')}" width="16" height="16" /> <a href="#" onclick="userspopup('{CreateUrl('userselect')}', 'assigned_to')">
                   {L('select')}</a>
                   <br />
                   <textarea cols="10" rows="4" name="assigned_to" id="assigned_to"><?php
                   if (!Req::val('assigned_to')):
                   foreach ($userlist as $usr):
                   ?>{$usr['user_name']}; <?php
                   endforeach;
                   else:
                   ?>{Req::val('assigned_to')}<?php
                   endif;
                   ?></textarea>
                   <span class="autocomplete hide" id="assigned_to_complete"></span>
                   <script type="text/javascript">
                      showstuff('assigned_to_complete');
                      new Ajax.Autocompleter('assigned_to', 'assigned_to_complete', '{$baseurl}javascript/callbacks/usersearch.php', {})
                   </script>

                   <?php if (isset($old_assigned)): ?>
                   <input type="hidden" name="old_assigned" value="<?php
                   foreach ($userlist as $usr):
                   ?>{$usr['user_id']} <?php
                   endforeach; endif; ?>" />
				</div>