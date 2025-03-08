const express = require('express');
const app = express();
const port = 3000;

app.use(express.json());

app.post('/calculate-discount', (req, res) => {
    const { customer_id, total_prices } = req.body;
    
    if (!customer_id || !total_prices) {
        return res.status(400).json({
            success: false,
            message: 'Invalid input',
        });
    }

    const discount = total_prices * 0.1; 
    const final_price = total_prices - discount;

    console.log('Discount calculated:', {
        customer_id, final_price
    });
    res.json({
        discount,
        final_price,
    });
});


app.post('/notify-order', (req, res) => {
    console.log('Order received:', req.body);

    res.status(200).json({
        success: true,
        message: 'Order notification received',
    });
});

app.listen(port, () => {
    console.log(`Microservice running on http://localhost:${port}`);
});
