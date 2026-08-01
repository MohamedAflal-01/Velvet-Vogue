<?php
$page_title = 'Contact Us';
require_once 'includes/header.php';

// Generate CSRF token
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    $token_post = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    if (!verify_csrf_token($token_post)) {
        set_flash_message('error', 'Security check failed. Please refresh and try again.');
        redirect('contact.php');
    }

    // Sanitize input
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    // Validation
    $errors = [];
    if (empty($name) || strlen($name) > 100) {
        $errors[] = "Name must be provided and under 100 characters.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($subject) || strlen($subject) > 255) {
        $errors[] = "Subject must be provided and under 255 characters.";
    }
    if (empty($message)) {
        $errors[] = "Message content cannot be blank.";
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);

            if ($stmt->execute()) {
                set_flash_message('success', 'Thank you for reaching out. Our concierge desk has received your message and will respond within 24 hours.');
                redirect('contact.php');
            } else {
                set_flash_message('error', 'We encountered an error saving your message. Please try again.');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Database connection error. Please try again later.');
        }
    } else {
        set_flash_message('error', implode(' ', $errors));
        redirect('contact.php');
    }
}
?>

<!-- Contact Hero Section -->
<div class="hero-section" style="background-image: url('https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?q=80&w=2070&auto=format&fit=crop'); height: 50vh;">
    <div class="hero-overlay" style="background: rgba(0,0,0,0.65);"></div>
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="text-gold text-uppercase fw-bold tracking-widest d-block mb-3 fade-in-up" style="letter-spacing: 4px;">Get In Touch</span>
                <h1 class="display-4 fw-bold text-white mb-3 fade-in-up">Velvet Vogue Concierge</h1>
                <p class="lead text-white-50 fade-in-up mb-0">We are dedicated to helping you with sizing, orders, and styling needs.</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 py-5">
    <div class="row g-5">
        <!-- Contact Information (Left Column) -->
        <div class="col-lg-5 fade-in-up">
            <span class="text-gold text-uppercase fw-bold d-block mb-2" style="letter-spacing: 2px;">Contact Information</span>
            <h2 class="text-dark mb-4 position-relative pb-3">
                Concierge Desk
                <span class="position-absolute bottom-0 start-0 bg-gold" style="width: 60px; height: 2px;"></span>
            </h2>
            <p class="text-muted mb-5">Have a question regarding our seasonal collections, sizes, bespoke tailoring, or existing orders? Reach out through any of our channels below. Our concierge team is standing by.</p>

            <!-- Info Items -->
            <div class="d-flex align-items-start mb-4">
                <div class="text-gold me-3 fs-4" style="width: 40px; text-align: center;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark text-uppercase mb-1">Flagship Boutique</h6>
                    <p class="text-muted mb-0">456 Rodeo Drive, Beverly Hills, CA 90210, US</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-4">
                <div class="text-gold me-3 fs-4" style="width: 40px; text-align: center;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark text-uppercase mb-1">Concierge Hotline</h6>
                    <p class="text-muted mb-0"><a href="tel:+13105550199" class="text-decoration-none text-muted">+1 (310) 555-0199</a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-4">
                <div class="text-gold me-3 fs-4" style="width: 40px; text-align: center;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark text-uppercase mb-1">Electronic Mail</h6>
                    <p class="text-muted mb-0"><a href="mailto:concierge@velvetvogue.com" class="text-decoration-none text-muted">concierge@velvetvogue.com</a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-5">
                <div class="text-gold me-3 fs-4" style="width: 40px; text-align: center;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark text-uppercase mb-1">Boutique Hours</h6>
                    <p class="text-muted mb-0">Monday – Saturday: 10:00 AM – 8:00 PM</p>
                    <p class="text-muted mb-0">Sunday: 12:00 PM – 6:00 PM PST</p>
                </div>
            </div>

            <!-- Social Links -->
            <div>
                <h6 class="fw-bold text-dark text-uppercase mb-3" style="letter-spacing: 1px;">Join Our Universe</h6>
                <div class="d-flex gap-3">
                    <a href="#" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; transition: all 0.3s;" onmouseover="this.style.color='var(--accent-color)'; this.style.borderColor='var(--accent-color)'" onmouseout="this.style.color='#212529'; this.style.borderColor='#212529'"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; transition: all 0.3s;" onmouseover="this.style.color='var(--accent-color)'; this.style.borderColor='var(--accent-color)'" onmouseout="this.style.color='#212529'; this.style.borderColor='#212529'"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; transition: all 0.3s;" onmouseover="this.style.color='var(--accent-color)'; this.style.borderColor='var(--accent-color)'" onmouseout="this.style.color='#212529'; this.style.borderColor='#212529'"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="btn btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; transition: all 0.3s;" onmouseover="this.style.color='var(--accent-color)'; this.style.borderColor='var(--accent-color)'" onmouseout="this.style.color='#212529'; this.style.borderColor='#212529'"><i class="fab fa-pinterest-p"></i></a>
                </div>
            </div>
        </div>

        <!-- Contact Form (Right Column) -->
        <div class="col-lg-7 fade-in-up">
            <div class="card glass-card p-5 border-0 shadow-sm" style="background: rgba(255,255,255,0.85); border-radius: 15px;">
                <span class="text-gold text-uppercase fw-bold d-block mb-2" style="letter-spacing: 1px;">Inquiries form</span>
                <h3 class="text-dark mb-4 text-uppercase fw-bold">Send A Message</h3>
                
                <form action="contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="name" class="form-label fw-bold text-uppercase small" style="letter-spacing: 1px;">Full Name</label>
                            <input type="text" class="form-control bg-white" id="name" name="name" required placeholder="e.g. John Doe" maxlength="100">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="email" class="form-label fw-bold text-uppercase small" style="letter-spacing: 1px;">Email Address</label>
                            <input type="email" class="form-control bg-white" id="email" name="email" required placeholder="e.g. john@example.com" maxlength="100">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="subject" class="form-label fw-bold text-uppercase small" style="letter-spacing: 1px;">Subject</label>
                        <input type="text" class="form-control bg-white" id="subject" name="subject" required placeholder="e.g. Order Inquiry / Bespoke Tailoring" maxlength="255">
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-bold text-uppercase small" style="letter-spacing: 1px;">Message</label>
                        <textarea class="form-control bg-white" id="message" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-luxury w-100 py-3 fw-bold">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Accordion Section -->
<div class="bg-light py-5 border-top border-bottom">
    <div class="container py-4">
        <span class="text-gold text-uppercase fw-bold d-block mb-2 text-center" style="letter-spacing: 2px;">Common Queries</span>
        <h2 class="text-dark mb-5 text-center position-relative pb-3">
            Frequently Asked Questions
            <span class="position-absolute bottom-0 start-50 translate-middle-x bg-gold" style="width: 50px; height: 2px;"></span>
        </h2>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush shadow-sm rounded overflow-hidden" id="faqAccordion">
                    
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="flush-headingOne">
                            <button class="accordion-button collapsed fw-bold text-dark py-4 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.95rem;" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                What are your standard shipping times?
                            </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted py-4">
                                We offer complimentary standard shipping on all orders over LKR 15,000. Domestic orders typically arrive within 2-4 business days. International express shipping usually takes 5-7 business days depending on customs clearance.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="flush-headingTwo">
                            <button class="accordion-button collapsed fw-bold text-dark py-4 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.95rem;" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                What is your return and exchange policy?
                            </button>
                        </h2>
                        <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted py-4">
                                We accept returns on all unworn, unwashed items in original saleable condition with all designer tags attached within 30 days of shipment receipt. Returns are complimentary for members. Custom bespoke orders are final sale.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="flush-headingThree">
                            <button class="accordion-button collapsed fw-bold text-dark py-4 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.95rem;" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                Do you offer customized fittings?
                            </button>
                        </h2>
                        <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted py-4">
                                Yes. Velvet Vogue offers complimentary bespoke tailoring and custom fitting appointments at all our flagship boutiques. Simply call our concierge hotline or make an appointment through email to schedule a session with a master stylist.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingFour">
                            <button class="accordion-button collapsed fw-bold text-dark py-4 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.95rem;" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFour" aria-expanded="false" aria-controls="flush-collapseFour">
                                How can I trace my consignment?
                            </button>
                        </h2>
                        <div id="flush-collapseFour" class="accordion-collapse collapse" aria-labelledby="flush-headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted py-4">
                                As soon as your order is dispatched from our boutique warehouse, you will receive a shipping confirmation email containing a carrier tracking link. Registered clients can also view order shipment status under the "Orders" page in their user account.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map Embed Section -->
<div class="container-fluid p-0">
    <div style="height: 450px; width: 100%;">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3304.4567285149363!2d-118.4035659847841!3d34.069399224888255!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c2bc07a72d3fcd%3A0xe1f448c4cf9c8b74!2sRodeo%20Dr%2C%20Beverly%20Hills%2C%20CA!5e0!3m2!1sen!2sus!4v1689500000000!5m2!1sen!2sus" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</div>

<?php require_once 'components/footer.php'; ?>
