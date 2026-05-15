document
.getElementById("resetPasswordForm")
.addEventListener("submit", function(e){

    e.preventDefault();

    const formData = new FormData(this);

    fetch("../ajax/reset_password.ajax.php", {

        method: "POST",
        body: formData

    })

    .then(response => response.json())

    .then(data => {

        document.getElementById("message").innerHTML =
            data.message;

        if(data.status === "success"){

            setTimeout(() => {

                window.location.href =
                "../auth/login.php";

            }, 2000);
        }
    });
});