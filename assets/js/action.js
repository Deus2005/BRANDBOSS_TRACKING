document.querySelectorAll(".menu-toggle").forEach(button => {

button.addEventListener("click", function(e){
    e.preventDefault();

    const dropdown = this.closest(".action-btn").querySelector(".action-dropdown");

    document.querySelectorAll(".action-dropdown").forEach(menu => {
        if(menu !== dropdown){
            menu.style.display = "none";
        }
    });

    dropdown.style.display =
        dropdown.style.display === "block" ? "none" : "block";
});
});
