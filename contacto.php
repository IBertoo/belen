<?php include __DIR__ . '/partials/header.php'; ?>

<section id="contacto" style="padding: 5vw; background: #fafafa;">
  <h2 style="font-size: clamp(1.5rem, 5vw, 2rem); text-align: center; margin-bottom: 2rem;">
    Contáctanos
  </h2>

  <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: stretch; justify-content: center; max-width: 1200px; margin: 0 auto;">
    
    <!-- Información -->
    <div style="flex: 1 1 350px; padding: 1rem; min-width: 280px;">
      <p><strong>Dirección:</strong> Z. 14 de Septiembre, Av. Buenos Aires, Edif. "Las Vegas"</p>
      <p>Piso P.B. Puesto 1, La Paz - Bolivia.</p>
      <p><strong>Teléfono:</strong> +591 67029969</p>
      <p><strong>Email:</strong> digistool@proton.me</p>
      <p>Síguenos en:
        <a href="#" style="text-decoration: none; color: #007bff;">Facebook</a> | 
        <a href="#" style="text-decoration: none; color: #007bff;">Instagram</a> | 
        <a href="#" style="text-decoration: none; color: #007bff;">LinkedIn</a>
      </p>
      <p>
        <a href="https://wa.me/59167029969?text=Hola%20quiero%20más%20información"
           target="_blank"
           style="display: inline-block; padding: 0.75rem 1.25rem; background: #25D366; color: white; font-weight: bold; border-radius: 0.5rem; text-decoration: none; font-size: clamp(0.9rem, 2.5vw, 1rem); transition: background 0.3s;">
          💬 Contáctanos por WhatsApp
        </a>
      </p>
    </div>

    <!-- Mapa -->
    <div style="flex: 1 1 350px; padding: 1rem; min-width: 280px;">
      <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 0.5rem;">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1912.8133156468346!2d-68.14494793585997!3d-16.494432813000167!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x915edf8a8e0f4297%3A0x744c84536ce19881!2sGaler%C3%ADa%20comercial%20%22LAS%20VEGAS%22!5e0!3m2!1ses-419!2sbo!4v1757878634218!5m2!1ses-419!2sbo"
          style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>

  </div>

  <!-- Modo responsive extra -->
  <style>
    @media (max-width: 768px) {
      #contacto div[style*="display: flex"] {
        flex-direction: column;
        align-items: center;
      }

      #contacto div[style*="flex: 1"] {
        max-width: 100%;
      }

      #contacto a[href*="wa.me"] {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</section>


<?php include __DIR__ . '/partials/footer.php'; ?>