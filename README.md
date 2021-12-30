### Email System using PHP Web API

###### EMAIL SYSTEM

Actors <br />
•	Admin <br />
•	Merchant <br />
•	Secondary Users <br />

Admin Roles <br />
•	Admin can send email <br />
•	Admin can see email listing <br />
•	Admin can see merchants <br />
•	Admin can see billing info <br />
•	Admin can see secondary users <br />
•	Admin can register secondary users <br />
•	Admin can recharge their account using stripe API <br />

Merchant Roles <br />
•	Merchant can register themselves <br />
•	Merchant can create secondary users <br />
•	Merchant can see billing info <br />
•	Merchant can see e-mail list <br />
•	Merchant can recharge their account using stripe API <br />

Secondary Users <br />
•	Can perform roles as assigned to them <br />
•	Roles of secondary users are as follow <br />
o	Email Sent <br />
o	View Email <br />
--------- 

Cost of each email is being deducted from user balance. <br />
Low balance emails are being sent. <br />


### APIs

<h2>Merchant Register API</h2>  <br />
Method: POST <br />
Parameters <br />
{ <br />
    "name": "Admin", <br />
    "email":"admin@mail.com", <br />
    "password":"123456", <br />
    "image":"imagetokeninbase64Code=" <br />
} <br />

Output <br />
{ <br />
    "success": 1, <br />
    "status": 201, <br />
    "message": "You have successfully registered." <br />
} <br />


<h2>Login</h2> <br />

Method: POST <br />

Parameters <br />
{ <br />
    "email":"someone@gmail.com", <br />
    "password":"123456" <br />
} <br />




Output <br />
{ <br />
    "success": 1, <br />
    "message": "You have successfully logged in.", <br />
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9. <br />eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3RcL3BocF9hdXRoX2FwaVwvIiwiYXVkIjoiaHR0cDpcL1wvbG9jYWxob3N0XC9waHBfYXV0aF9hcGlcLyIsImlhdCI6MTYzNTMzODA2NCwiZXhwIjoxNjM1MzQxNjY0LCJkYXRhIjp7InVzZXJfaWQiOiI1NSJ9fQ.a7sxj2kuynFbnig4otCeFgJGTbwQ1sxfyY97tX43nRI"
} <br />


<h2>Send Email</h2> <br />
Method: POST <br />

Parameters <br />
•	{ <br />
•	    "password”: “password", <br />
•	    "from":"someone@gmail.com", <br />
•	    "to":"sentto@gmail.com", <br />
•	    "subject": "Email Sent by Me", <br />
•	    "body”: "New Email" <br />
•	} <br />

Output <br />
Email has been sent <br />

<h2>See Listing</h2> <br />
Method: GET <br />

Parameters <br />
None <br />

Output <br />
{ <br />
    "emails": [ <br />
        { <br />
            "to": "someone@gmail.com", <br />
            "from": "sentfrom@gmail.com", <br />
            "subject": "Email Sent me", <br />
            "body": "New Email" <br />
        } <br />
    ] <br />
} <br />


<h2>Register Secondary Users</h2> <br />
Method: POST <br />

Duty parameters: “accountCreator” OR “emailViewer” <br />
Parameters <br />
{ <br />
    "name”: “Second", <br />
    "email":"seconduser@gmail.com", <br />
    "password":"123456", <br />
    "duty”: “accountCreator", <br />
    "image":"ImageTokenBase64Code=" <br />
} <br />

Output <br />
{ <br />
    "success": 1, <br />
    "status": 201, <br />
    "message": "You have successfully registered." <br />
} <br />

<h2>Stripe Payment</h2> <br />
Method: POST <br />
Header: Authorization => Bearer token <br />

If user is not authorized <br />
{ <br />
    "success": 0, <br />
    "status": 401, <br />
    "message": "You are not authorized" <br />
} <br />
Else <br />
{ <br />
    "success": 1, <br />
    "status": 201, <br />
    "message": "Your Payment received successfully" <br />
} <br />


<h2>Low Balance</h2> <br />
Method: POST <br />

Parameters <br />
{ <br />
    "password”: “password", <br />
    "from”: “email" <br />
} <br />

Output <br />
{ <br />
    "message": "Email sent to, mail@gmail.com" <br />
}{ <br /> 
    "message": "Email sent to, mail2@gmail.com" <br />
}[] <br />


<h2>See Billing</h2> <br />
Method: GET <br />

Parameters <br />
None <br />


Output <br />
{ <br />
    "billings": [ <br />
        {<br />
            "id": "6",<br />
            "customer_id": "55",<br />
            "amount": "10", <br />
            "card_number": "4242424242424242",<br />
            "exp_month": "12", <br />
            "exp_year": "2022", <br />
            "cvc": "123", <br />
            "created_at": "1635272723" <br />
        },
        { <br />
            "id": "7", <br />
            "customer_id": "61", <br />
            "amount": "10", <br />
            "card_number": "4242424242424242", <br />
            "exp_month": "12", <br />
            "exp_year": "2022", <br />
            "cvc": "123", <br />
            "created_at": "1635275654" <br />
        }, <br />
        { <br />
            "id": "8", <br />
            "customer_id": "55", <br />
            "amount": "10", <br />
            "card_number": "4242424242424242", <br />
            "exp_month": "12", <br />
            "exp_year": "2022", <br />
            "cvc": "123", <br />
            "created_at": "1635339554" <br />
        } <br />
    ] <br />
}[] <br />

<h2>See users</h2> <br />
Method: GET <br />

Output <br />
{ <br />
    "billings": [ <br />
        { <br />
            "id": "61", <br />
            "name": "Second", <br />
            "email": "seconduser@gmail.com", <br />
            "password": password", <br />
            "role": "S_USER", <br />
            "duty": "accountCreator", <br />
            "balance": "10" <br />
        } <br />
    ] <br />
}[] <br />



Output <br />
{ <br />
    "billings": [ <br />
        { <br />
            "id": "55", <br />
            "name": "name", <br />
            "email": "name@gmail.com", <br />
            "password": "$2y$10$sdasd/uA1z7USEDMRMdeLWSle", <br />
            "role": "MERCHANT", <br />
            "balance": "10.9511" <br />
        }, <br />
        { <br />
            "id": "64", <br />
            "name": "name2", <br />
            "email": "name2@mail.com", <br />
            "password": "$2y$10$da.llGZp1SpXKj84UssssrV8OBXa", <br />
            "role": "MERCHANT", <br />
            "balance": "0" <br />
        } <br />
    ] <br />
}[] <br />
