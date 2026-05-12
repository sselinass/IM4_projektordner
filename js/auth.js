async function requireAuth() {
  const response = await fetch("/api/protected.php", {
    credentials: "include",
  });

  if (response.status === 401) {
    window.location.href = "/index.html";
    return null;
  }

  return response.json(); // { email, user_id }
}