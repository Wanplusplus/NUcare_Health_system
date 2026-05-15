document
.getElementById("forgotPasswordForm")
.addEventListener("submit", function(e){

    e.preventDefault();

    const formData = new FormData(this);

    fetch("../ajax/forgot_password.ajax.php", {

        method: "POST",
        body: formData

    })

    .then(response => response.json())

    .then(data => {

        document.getElementById("message").innerHTML =
            data.message;

    });

});