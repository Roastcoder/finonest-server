/**
 * SMS Service for OTP Delivery
 * Supports Twilio and AWS SNS providers
 */

interface SMSConfig {
  provider: 'twilio' | 'aws-sns' | 'console';
  twilio?: {
    accountSid: string;
    authToken: string;
    fromNumber: string;
  };
  awsSns?: {
    region: string;
    accessKeyId: string;
    secretAccessKey: string;
  };
}

class SMSService {
  private config: SMSConfig;

  constructor() {
    this.config = {
      provider: (process.env.SMS_PROVIDER as 'twilio' | 'aws-sns' | 'console') || 'console',
      twilio: {
        accountSid: process.env.TWILIO_ACCOUNT_SID || '',
        authToken: process.env.TWILIO_AUTH_TOKEN || '',
        fromNumber: process.env.TWILIO_FROM_NUMBER || '',
      },
      awsSns: {
        region: process.env.AWS_REGION || 'us-east-1',
        accessKeyId: process.env.AWS_ACCESS_KEY_ID || '',
        secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY || '',
      },
    };
  }

  /**
   * Send OTP SMS to phone number
   */
  async sendOTP(phone: string, code: string, expiryMinutes: number = 10): Promise<void> {
    const message = `Your Finonest OTP is ${code}. Valid for ${expiryMinutes} minutes. Do not share this code with anyone.`;

    switch (this.config.provider) {
      case 'twilio':
        await this.sendViaTwilio(phone, message);
        break;
      case 'aws-sns':
        await this.sendViaAWSSNS(phone, message);
        break;
      case 'console':
      default:
        console.log(`[SMS] To: ${phone}\nMessage: ${message}`);
        break;
    }
  }

  /**
   * Send SMS via Twilio
   */
  private async sendViaTwilio(phone: string, message: string): Promise<void> {
    if (!this.config.twilio?.accountSid || !this.config.twilio?.authToken) {
      throw new Error('Twilio credentials not configured');
    }

    try {
      // Format phone number for Twilio (E.164 format: +91XXXXXXXXXX)
      const formattedPhone = phone.startsWith('+') ? phone : `+91${phone}`;

      const twilio = require('twilio');
      const client = twilio(this.config.twilio.accountSid, this.config.twilio.authToken);

      await client.messages.create({
        body: message,
        from: this.config.twilio.fromNumber,
        to: formattedPhone,
      });

      console.log(`[Twilio] OTP sent to ${formattedPhone}`);
    } catch (error: any) {
      console.error('[Twilio] SMS send error:', error);
      throw new Error(`Failed to send SMS via Twilio: ${error.message}`);
    }
  }

  /**
   * Send SMS via AWS SNS
   */
  private async sendViaAWSSNS(phone: string, message: string): Promise<void> {
    if (!this.config.awsSns?.accessKeyId || !this.config.awsSns?.secretAccessKey) {
      throw new Error('AWS SNS credentials not configured');
    }

    try {
      // Dynamic import to avoid requiring aws-sdk if not used
      const AWS = await import('aws-sdk');
      const sns = new AWS.SNS({
        region: this.config.awsSns.region,
        accessKeyId: this.config.awsSns.accessKeyId,
        secretAccessKey: this.config.awsSns.secretAccessKey,
      });

      // Format phone number for AWS SNS (E.164 format: +91XXXXXXXXXX)
      const formattedPhone = phone.startsWith('+') ? phone : `+91${phone}`;

      const params = {
        Message: message,
        PhoneNumber: formattedPhone,
        MessageAttributes: {
          'AWS.SNS.SMS.SMSType': {
            DataType: 'String',
            StringValue: 'Transactional',
          },
        },
      };

      await sns.publish(params).promise();
      console.log(`[AWS SNS] OTP sent to ${formattedPhone}`);
    } catch (error: any) {
      console.error('[AWS SNS] SMS send error:', error);
      throw new Error(`Failed to send SMS via AWS SNS: ${error.message}`);
    }
  }

  /**
   * Validate phone number format
   */
  static validatePhone(phone: string): boolean {
    // Indian mobile numbers: 10 digits starting with 6-9
    const phoneRegex = /^[6-9]\d{9}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
  }

  /**
   * Format phone number to E.164
   */
  static formatPhone(phone: string): string {
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 10 && cleaned.startsWith('6') || cleaned.startsWith('7') || cleaned.startsWith('8') || cleaned.startsWith('9')) {
      return `+91${cleaned}`;
    }
    return phone.startsWith('+') ? phone : `+${phone}`;
  }
}

export default new SMSService();
