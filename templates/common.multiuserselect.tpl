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
                        var boxl = findPos($('userselect'))[0];
                        var boxr = boxl + $('userselect').offsetWidth;
                        var scrm = Math.floor(window.innerWidth/2);
                        var dif1 = boxl-scrm;
                        var dif2 = boxr-scrm;
                        if ((dif1<=0 && dif2<=0) || (Math.abs(dif1) < Math.abs(dif2))) {
                          // box is completely on left half of screen or more left than right
                          var winleft = boxr+30;
                          var winwidth = window.innerWidth-winleft-30;
                          if (winwidth < 500) winleft -= (500-winwidth);
                        } else {
                          // box is completely on right half of screen or more right than left
                          var winleft = 30;
                          var winwidth = boxl-30;
                          if (winwidth < 500) winwidth += (500-winwidth);
                        }
                        newwindow=window.open(url,'name','height=' + Math.min(window.innerHeight, 650) + ',width=' + Math.min(winwidth, 550) + ',left=' + winleft);
                        if (!newwindow.opener) newwindow.opener = self;
                    }
                    if (window.focus) { newwindow.focus(); }
                    this.onfocus  = closeme;
                    return false;
                }
                </script>
