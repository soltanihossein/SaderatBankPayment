<?php namespace Dpsoft\Saderat;


use Dpsoft\Saderat\Exception\SaderatException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Respect\Validation\Exceptions\ValidationException;

class SaderatResponse extends ResponseData
{
    /**
     * Saderat verify url
     *
     * @var string
     *
     */
    const VERIFY_URl = "https://Saderat.shaparak.ir:8081/V1/PeymentApi/Advice";

    /**
     * Saderat rollback url
     *
     * @var string
     *
     */
    const ROLLBACK_URl = "https://Saderat.shaparak.ir:8081/V1/PeymentApi/Rollback";

    /**
     * Terminal ID
     *
     * @var int
     *
     */
    private $terminalId;

    /**
     * @var Client
     */
    private $client;


    /**
     * Response constructor.
     *
     * @param int $terminalId
     * @param array $post
     *
     * @throw ValidationException
     *
     */
    public function __construct(int $terminalId, array $post = [])
    {
        if (empty($post)) {
            $post = $_POST;
        }
        $this->getPostVariables($post);
        $this->terminalId = $terminalId;
    }


    /**
     * Verify and get data of transaction
     *
     * @return SaderatResponse
     *
     * @throws SaderatException
     * @throws RequestException
     *
     */
    public function verify()
    {
        if ($this->getRespCode() == 0) {
            $this->client = $this->client ?? new Client();

            $body = $this->client->post(
                self::VERIFY_URl, [
                    'form_params' => [
                        'digitalreceipt' => $this->getDigitalReceipt(),
                        'Tid' => $this->terminalId,
                    ]]
            )->getBody();

            $verifyResponse = json_decode($body, true);

            if (!empty($verifyResponse['Status']) and $verifyResponse['Status']
                == 'Ok' and $verifyResponse['ReturnId'] == $this->getAmount()
            ) {
                return $this;
            } else {
                throw new SaderatException($verifyResponse['ReturnId'] ?? -8);
            }
        } elseif ($this->getRespCode() == -1) {
            throw new SaderatException(-7);
        } else {
            throw new SaderatException(-8);
        }
    }


    /**
     * @param string $digitalReceipt The receipt code of transaction
     *
     * @return bool
     *
     * @throws SaderatException
     * @throws RequestException
     */
    public function rollbackPayment(string $digitalReceipt)
    {
        $this->client = $this->client ?? new Client();

        $body = $this->client->post(
            self::ROLLBACK_URl, [
                'form_params' => [
                    'digitalreceipt' => $digitalReceipt,
                    'Tid' => $this->terminalId,
                ]]
        )->getBody();

        $rollbackResponse = json_decode($body, true);

        if (!empty($rollbackResponse['Status']) and $rollbackResponse['Status'] == 'Ok') {
            return true;
        } else {
            throw new SaderatException($rollbackResponse['ReturnId'] ?? -8);
        }
    }


    /**
     * @param array $transactionResponse
     *
     * @throws ValidationException
     */
    public function getPostVariables(array $transactionResponse)
    {
        $this->setRespCode($transactionResponse['respcode']);
        $this->setAmount($transactionResponse['amount']);
        $this->setInvoiceId($transactionResponse['invoiceid']);
        $this->setPayload($transactionResponse['payload']);
        $this->setTerminalId($transactionResponse['terminalid']);
        $this->setTraceNumber($transactionResponse['tracenumber']);
        $this->setRRN($transactionResponse['rrn']);
        $this->setDatePaid($transactionResponse['datepaid']);
        $this->setDigitalReceipt($transactionResponse['digitalreceipt']);
        $this->setIssuerBank($transactionResponse['issuerbank']);
        $this->setRespMsg($transactionResponse['respmsg']);
        $this->setCardNumber($transactionResponse['cardnumber']);
    }


    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

}
