                <div>
                   <img src="{$this->get_image('kuser-small')}" width="16" height="16" /> <a href="#" onclick="popitup('{CreateUrl('userselect')}')">
                   {L('select')}</a>
                   <br />
                   <textarea cols="10" rows="4" name="assigned_to" id="userselect"><?php
                   if (!Req::val('assigned_to')):
                   foreach ($userlist as $usr):
                   ?>{$usr['user_name']}; <?php
                   endforeach; ?>
                   <?php else:
                   ?>{Req::val('assigned_to')}<?php
                   endif; ?>
                   </textarea>

                   <?php if (isset($old_assigned)): ?>
                   <input type="hidden" name="old_assigned" value="<?php
                   foreach ($userlist as $usr):
                   ?>{$usr['user_id']} <?php
                   endforeach; endif; ?>" />
				</div>
                <script type="text/javascript">
                function findPos(obj) {
                    var curleft = curtop = 0;
                    if (obj.offsetParent) {
                        curleft = obj.offsetLeft
                        curtop = obj.offsetTop
                        while (obj = obj.offsetParent) {
                            curleft += obj.offsetLeft
                            curtop += obj.offsetTop
                        }
                    }
                    return [curleft,curtop];
                }

                var newwindow = '';
                function closeme() { if (!newwindow.closed) newwindow.close() }
                function popitup(url) {
                    if (!newwindow.closed && newwindow.location) {
                        newwindow.location.href = url;
                    }
                    else {
                        newwindow=window.open(url,'name','height=700,width=550,left=' + (findPos($('userselect'))[0] + $('userselect').offsetWidth + 50));
                        if (!newwindow.opener) newwindow.opener = self;
                    }
                    if (window.focus) { newwindow.focus(); }
                    this.onfocus  = closeme;
                    return false;
                }
                </script>
