function goToGame() {
  window.location.href = "../GamePage/GamePage.php";
}

// Modal logic for logout
(function () {
  const logoutForm = document.getElementById("logoutForm");
  const logoutBtn = document.getElementById("logoutBtn");
  const modal = document.getElementById("logoutModal");
  const cancelBtn = document.getElementById("cancelLogout");
  const confirmBtn = document.getElementById("confirmLogout");

  // Show modal helper
  function openModal() {
    modal.style.display = "flex";
    modal.setAttribute("aria-hidden", "false");
    // trap focus on confirm button
    confirmBtn.focus();
    document.body.style.overflow = "hidden";
  }

  function closeModal() {
    modal.style.display = "none";
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    logoutBtn.focus();
  }

  // Intercept form submit to show modal instead of immediate submit
  logoutForm.addEventListener("submit", function (e) {
    e.preventDefault();
    openModal();
  });

  // Cancel closes the modal
  cancelBtn.addEventListener("click", function (e) {
    e.preventDefault();
    closeModal();
  });

  // Confirm submits the form
  confirmBtn.addEventListener("click", function (e) {
    // perform the original logout POST
    // optionally we can disable the button to prevent double submit
    confirmBtn.disabled = true;
    confirmBtn.textContent = "Logging out...";
    logoutForm.submit();
  });

  // Close modal on overlay click (if user clicks outside the modal area)
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closeModal();
  });

  // Close modal on Escape
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && modal.style.display === "flex") {
      closeModal();
    }
  });
})();
