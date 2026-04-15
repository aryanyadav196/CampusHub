        </main>
        <footer class="site-footer">
            <p>&copy; <?php echo date("Y"); ?> CampusHub. Trusted campus operations for modern institutions.</p>
        </footer>
    </div>
</div>
<button class="chat-fab" type="button" data-chat-toggle aria-label="Open assistant">
    <span>Assistant</span>
</button>
<section class="chat-shell" data-chat-shell data-chat-endpoint="<?php echo e($basePath); ?>chatbot.php" hidden>
    <header class="chat-header">
        <div>
            <strong>Campus Assistant</strong>
            <p>Ask about students, books, events, and records.</p>
        </div>
        <button class="chat-close" type="button" data-chat-close aria-label="Close assistant">&times;</button>
    </header>
    <div class="chat-messages" data-chat-messages>
        <article class="chat-message is-bot">
            <div class="chat-bubble">
                I can answer questions about student records, library activity, and event schedules.
            </div>
        </article>
    </div>
    <form class="chat-form" data-chat-form>
        <input type="text" name="message" placeholder="Ask about students, books, or events" maxlength="500" required>
        <button type="submit">Send</button>
    </form>
</section>
<script src="<?php echo e($basePath); ?>assets/app.js"></script>
</body>
</html>
