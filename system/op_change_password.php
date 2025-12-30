<?php require_once('all_header.php'); ?>

<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
            <a href='index' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>
    
            Change Password</h1>
         
        </div>

        <div class="row">
           <div class="col-12">
             <div class="card">
                   <div class="card-body">
                        <div class="row justify-content-center">
                        <div class="col-md-4 col-md-offset-4">
                         <form action='change_password' id='update_frm' method='post' role="form" type="system">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input class="form-control" type='password' id='current_password' name='current_password' required>
    
                                </div>
    
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input class="form-control" type='password' id='new_password' name='new_password' required minlength='5'>
                                    <span id="StrengthDisp" class="badge displayBadge badge-light text-light float-right mt-2 p-1">Weak</span>
                                </div>
    
                                <div class="form-group">
                                    <label>Confirm Password <span id='matched' class='badge badge-light'> </span> </label>
                                    <input class="form-control" id='repeat_password' type='password' required minlength='5'>
    
                                </div>
                            </form>
                            <input type="button" class="btn btn-dark btn-block mt-2" id='change_password' value='Change Password'>
                      </div>
                    </div>
                </div>
             </div>
          </div>
       </div>
<?php require_once('footer.php'); ?>

<script>
    $(document).on('keyup', "#repeat_password", function() {
        var a = $("#new_password").val();
        var b = $("#repeat_password").val();
        if (a == b) {
            $("#matched").html("<b class ='text-success mt-1'> Matched </b>");
            $("#change_password").attr("disabled", false);
        } else {
            $("#matched").html("<b class ='text-danger mt-1'> Not Matched </b>");
            $("#change_password").attr("disabled", true);
        }
    });
    // timeout before a callback is called

    let timeout;

    // traversing the DOM and getting the input and span using their IDs

    let password = document.getElementById('new_password')
    let strengthBadge = document.getElementById('StrengthDisp')

    // The strong and weak password Regex pattern checker

    let strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})')
    let mediumPassword = new RegExp('((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{6,}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=.{8,}))')

    function StrengthChecker(PasswordParameter) {
        // We then change the badge's color and text based on the password strength

        if (strongPassword.test(PasswordParameter)) {
            strengthBadge.style.backgroundColor = "green"
            strengthBadge.textContent = 'Strong'
        } else if (mediumPassword.test(PasswordParameter)) {
            strengthBadge.style.backgroundColor = 'skyblue'
            strengthBadge.textContent = 'Medium'
        } else {
            strengthBadge.style.backgroundColor = 'orangered'
            strengthBadge.textContent = 'Weak'
        }
    }

    // Adding an input event listener when a user types to the  password input 

    password.addEventListener("input", () => {

        //The badge is hidden by default, so we show it

        strengthBadge.style.display = 'block'
        clearTimeout(timeout);

        //We then call the StrengChecker function as a callback then pass the typed password to it

        timeout = setTimeout(() => StrengthChecker(password.value), 500);

        //Incase a user clears the text, the badge is hidden again

        if (password.value.length !== 0) {
            strengthBadge.style.display != 'block'
        } else {
            strengthBadge.style.display = 'none'
        }
    });
</script>