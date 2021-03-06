<?php
script('b2sharebridge', 'settings-personal');
?>
<div class="section" id="eudat_b2share">
    <h2>EUDAT B2SHARE Bridge</h2>
    <p id="b2shareUrlField">
        <input title="publish_baseurl" type="text" id="b2shareUrl"
               value="<?php p($_['publish_baseurl']); ?>"
               style="width: 400px" disabled/>
        <em>External publishing endpoint</em>
    </p>
    <p id="b2shareAPITokenField">
        <input title= "b2share API token" type="text"  id="b2share_apitoken" value="<?php p($_['b2share_apitoken']); ?>" name="b2share_apitoken"
               style="width: 400px" />
        <em>B2Share API token</em>
        <div id="lostpassword"><span class="msg"></span><br /></div>
    </p>
    <p id="b2shareManageAPIToken">
        <button id="b2share_save_apitoken" href="#">Save B2SHARE API Token</button>
        <button id="b2share_delete_apitoken" href="#">Delete B2SHARE API Token</button>
    </p>
</div>