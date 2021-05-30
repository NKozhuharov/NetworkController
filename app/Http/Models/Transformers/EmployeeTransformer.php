<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\Employee;
use League\Fractal\TransformerAbstract;

class EmployeeTransformer extends TransformerAbstract
{
    /**
     * @param Employee|null $employee
     *
     * @return array
     */
    public function transform(Employee $employee = NULL): array
    {
        if ($employee) {
            $response = [
                Employee::F_ID            => (int)$employee->{Employee::F_ID},
                Employee::F_NAME          => (string)$employee->{Employee::F_NAME},
                Employee::F_SECOND_NAME   => (string)$employee->{Employee::F_SECOND_NAME},
                Employee::F_SURNAME       => (string)$employee->{Employee::F_SURNAME},
                Employee::F_EGN           => (string)$employee->{Employee::F_EGN},
                Employee::F_ID_NUMBER     => (string)$employee->{Employee::F_ID_NUMBER},
                Employee::F_GENDER        => (string)$employee->{Employee::F_GENDER},
                Employee::F_DATE_OF_BIRTH => (string)$employee->{Employee::F_DATE_OF_BIRTH},
                Employee::F_COUNTRY_ID    => (int)$employee->{Employee::F_COUNTRY_ID},
                Employee::F_ADDRESS       => (string)$employee->{Employee::F_ADDRESS},
                Employee::F_ID_ISSUED_ON  => (string)$employee->{Employee::F_ID_ISSUED_ON},
                Employee::F_ID_EXPIRES_ON => (string)$employee->{Employee::F_ID_EXPIRES_ON},
                Employee::F_PHONE         => (string)$employee->{Employee::F_PHONE},
                Employee::F_EMAIL         => (string)$employee->{Employee::F_EMAIL},
                Employee::F_TYPE          => (string)$employee->{Employee::F_TYPE},
                Employee::F_STATUS        => (string)$employee->{Employee::F_STATUS},
                Employee::F_FILES         => (array)$employee->{Employee::F_FILES},
                Employee::F_USER_ID       => (int)$employee->{Employee::F_USER_ID},
                Employee::F_CREATED_AT    => (int)$employee->{Employee::F_CREATED_AT},
            ];
        }
        return $response ?? [];
    }

    public function includeUser(Employee $employee)
    {
        return $employee->user()
            ? $this->item($employee->{Employee::FR_USER}, new UserTransformer())
            : $this->null();
    }
}
