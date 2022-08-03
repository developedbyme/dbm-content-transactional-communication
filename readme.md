Transactional communcaiton for Dbm content

# API

## Data structures

### Enum: InitialSendType

- both : Send both email and text message if contact details exist for each method
- preferTextMessage : Send only text message if contact details exists, otherwise send email

### Enum: SendTypeOption

- email
- textMessage

### User

```
{
	id: int,
	permalink: url,
	name: string,
	gravatarHash: string
}
```

### UserWithPrivateData extends User

```
{
	id: int (from User),
	permalink: url (from User),
	firstName: string,
	lastName: string,
	name: string (from User),
	email: string,
	gravatarHash: string (from User)
}
```

## Endpoints

### Send password reset verification

Sends a password reset verification, 6 digit number, by email and/or text message.

#### Request

POST `/wp-json/wprr/v1/action/dbmtc/sendPasswordResetVerification`

```
{
	sendType: InitialSendType (default: default),
	user: id (int) or email (string)
}
```

#### Response

```
{
	code: "success",
	data: {
		verificationId: int,
		sent: array of SendTypeOption
		availableOptions: array of SendTypeOption
	}
}
```

#### Errors

```
{
	code: "success",
	data: {
		message: "User not found"
	}
}
```

### Re-send password reset verification

Resends a previously sent verification

#### Request

POST `/wp-json/wprr/v1/action/dbmtc/resendPasswordResetVerification`

```
{
	verificationId: int,
	sendType: SendTypeOption
}
```

#### Response

```
{
	code: "success",
	data: {
		sent: array of SendTypeOption
	}
}
```

### Verify a password reset

Use the verification id and the code that the user has inputted in order to verify the reset

#### Request

POST `/wp-json/wprr/v1/action/dbmtc/verifyResetPassword`

```
{
	user: id (int) or email (string),
	verificationId: int,
	verificationCode: int
}
```

#### Response

```
{
	code: "success",
	data: {
		verified: boolean
	}
}
```

### Set password with verification

Sets the user password after verification, and logs in the user

#### Request

POST `/wp-json/wprr/v1/action/dbmtc/setPasswordWithVerification`

```
{
	user: id (int) or email (string),
	verificationId: int,
	password: string
}
```

#### Response

```
{
	code: "success",
	data: {
		authenticated: boolean
		[userId: int],
		[user: UserWithPrivateData],
		[roles: array of string],
		[restNonce: string],
		[restNonceGeneratedAt: unix timestamp]
	}
}
```