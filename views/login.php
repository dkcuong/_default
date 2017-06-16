<?php

class view extends controller
{

    function userLoginView()
    {
        if ($this->codeSent) { ?>

            <div class="container">
                <div id="resetMessage" class="successMessage">
                    Check your email. You
                    have been sent instructions to reset your password.
                </div>
            </div>

        <?php }
        if ($this->tooManyFails) { ?>

            <div class="container">
                <div id="failedLogin">
                    This application has been temporarily suspended.
                    Please try to login later.
                </div>
            </div>

        <?php } ?>

        <form id="loginForm" method="post">
            <label>Username:</label>
            <input type="text" name="username" />
            <label><?php echo $this->passTitle; ?>:</label>
            <input type="<?php echo $this->inputType; ?>" name="password"  />
            <input type="submit" value="Submit" name="submit" class="submit" />
        </form>
        <a href="<?php echo $this->passwordLink; ?>">
            <?php echo $this->recoverTitle; ?>
        </a>

        <?php
    }

    /*
    ****************************************************************************
    */

    function changePasswordLoginView()
    {
        if ($this->success) { ?>

            <div id="resetMessage">
                <?php echo $this->success; ?>
            </div>

        <?php }

        if ($this->error) { ?>

            <div id="failedLogin">
                <?php echo $this->error; ?>
            </div>

        <?php } ?>

        <form id="changePass" method="post">
            <table>
                <tr>
                    <td>New Password</td>
                    <td>
                        <input name="newPass" type="password">
                    </td>
                </tr>
                <tr>
                    <td>Confirm New Password</td>
                    <td>
                        <input name="confirmPass" type="password">
                    </td>
                </tr>
                <tr>
                    <td id="changeSubmit" colspan="2">
                        <input value="Submit" type="submit">
                    </td>
                </tr>
            </table>
        </form>

        <?php
    }

    /*
    ****************************************************************************
    */
}