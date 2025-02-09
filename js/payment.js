const stripe = Stripe('');
const elements = stripe.elements();
const card = elements.create('card');
card.mount('#card-element');

card.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if(event.error) {
        displayError.textContent = event.error.message;
    } else {
        displayError.textContent = '';
    }
});

const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');

form.addEventListener('submit', async function(event) {
    event.preventDefault();
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    showLoading('Initializing payment...');

    try {
        const bookingStatusResponse = await fetch('../api/check_booking_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ booking_id: bookingId })
        });
        const bookingStatus = await bookingStatusResponse.json();
        if(bookingStatus.status !== 'pending') {
            throw new Error('This booking is no longer valid.');
        }

        showLoading('Processing payment...');
        const response = await fetch('../api/create_payment_intent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ booking_id: bookingId })
        });
        if(!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || "Payment initialization failed.");
        }

        const { clientSecret, payment_intent_id } = await response.json();

        showLoading('Confirming payment...');
        const result = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: card,
                billing_details: { name: bookingName }
            }
        });
        if(result.error) {
            switch(result.error.code) {
                case 'card_declined':
                    throw new Error("Card declined. Please try another card.");
                case 'expired_card':
                    throw new Error("Card expired. Please try another card.");
                case 'incorrect_cvv':
                    throw new Error("CVV is incorrect. Please try again.");
                case 'insufficient_funds':
                    throw new Error("Insufficient funds. Please try another card.");
                default:
                    throw new Error(result.error.message);
            };
        }

        showLoading('Finalizing your booking...');
        const updateResponse = await fetch('../api/update_booking_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                booking_id: bookingId,
                payment_intent_id: result.paymentIntent.id
            })
        });
        if(!updateResponse.ok) {
            throw new Error('Payment successful but booking update failed. Please contact our customer support.');
        }

        window.location.href = `booking_confirmation.php?booking_id=${bookingId}`;
    } catch(error) {
        hideLoading();
        showError(error.message);
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-lock"></i> Try Again';

        console.error('Payment error:', error);
        if(error.message.includes('booking update failed')) {
            showError(
                'Payment successful but booking update failed. Please contact our customer support: ' +
                'support@socar.com or contact 1-800-SOCAR with your booking ID.',
                true
            );
        }
    }
});