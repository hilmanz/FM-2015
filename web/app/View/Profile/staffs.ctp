<div id="fillDetailsPage">
	
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Pilih Staff Anda</h1>
                    <p>Tentukan sendiri staff mana yang akan Anda rekrut untuk membantu Anda mengelola tim dan klab secara maksimal. Pilih dengan bijak dan sesuaikan dengan kondisi keuangan.</p>
                </div><!-- end .row-2 -->
                <form class="theForm">
                    <div class="row-2">
                        <div class="col2">
                            <select class="styled">
                                <option>Marketing</option>
                            </select>
                        </div><!-- end .col2 -->
                        <div class="col2">
                            <div class="searchBox">
                                <input type="text" value="Search" />
                                <input type="submit" value="&nbsp;" />
                            </div>
                        </div>
                    </div><!-- end .row-2 -->
                    <div class="row-2 titles">
                        <div class="col2">
                            <h2>Select Staff Member</h2>
                        </div><!-- end .col2 -->
                        <div class="col2">
                            <h2>Selected</h2>
                        </div>
                    </div><!-- end .row-2 -->
                    <div class="row-2">
                        <div class="col2 staff-list">
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="content/thumb/default_avatar.png" />
                                </div><!-- end .avatar-big -->
                                <h3>Marketing Manager</h3>
                            </div><!-- end .thumbStaff -->
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="content/thumb/default_avatar.png" />
                                </div><!-- end .avatar-big -->
                                <h3>Marketing Staff</h3>
                            </div><!-- end .thumbStaff -->
                        </div><!-- end .col2 -->
                        <div class="col2 staff-list">
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="content/thumb/default_avatar.png" />
                                </div><!-- end .avatar-big -->
                                <h3>Marketing Staff</h3>
                            </div><!-- end .thumbStaff -->
                        </div>
                    </div><!-- end .row-2 -->
                    <div class="row-2">
                        <input type="submit" value="Save &amp; Continue" class="button" />
                    </div><!-- end .row-2 -->
                </form>
            </div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    <div class="widget">
	        <div class="nav-side">
	            <ul>
	               <li><a href="details">Profile Anda</a></li>
	               <li><a href="teams">Pilih Tim Anda</a></li>
	               <li><a href="players">Pilih Pemain</a></li>
	               <li class="current"><a href="staffs">Pilih Staff</a></li>
	               <li><a href="clubs">Pilih Klab</a></li>
	               <li><a href="formations">Mengatur Formasi</a></li>
	               <li><a href="invite_friends">Undang Teman</a></li>
	            </ul>
	        </div><!-- end .nav-side -->
	    </div><!-- end .widget -->
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">Cash Left</h3>
	            <h1>SS$ <?=number_format($team_bugdet)?></h1>
	            <h3 class="red">Est. Monthly Expenses</h3>
	            <h1>SS$ 0</h1> 
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->